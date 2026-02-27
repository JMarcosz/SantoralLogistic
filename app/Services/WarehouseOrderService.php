<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\WarehouseOrderStatus;
use App\Models\InventoryMovement;
use App\Models\ShippingOrder;
use App\Models\Warehouse;
use App\Models\WarehouseOrder;
use App\Models\WarehouseOrderLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Warehouse Orders (Pick/Pack/Dispatch operations).
 *
 * Handles the creation of warehouse orders from shipping orders,
 * state transitions, and inventory picking operations.
 */
class WarehouseOrderService
{
    public function __construct(
        private InventoryReservationService $reservationService
    ) {}

    /**
     * Create a Warehouse Order from a Shipping Order using its reservations.
     *
     * @param ShippingOrder $shippingOrder The shipping order to create WO from
     * @param Warehouse $warehouse The warehouse where operations will be performed
     * @param string|null $reference Optional external reference
     * @param string|null $notes Optional notes
     * @return WarehouseOrder The created warehouse order
     * @throws \InvalidArgumentException When validation fails
     */
    public function createFromShippingOrder(
        ShippingOrder $shippingOrder,
        Warehouse $warehouse,
        ?string $reference = null,
        ?string $notes = null
    ): WarehouseOrder {
        // Validate SO status
        if (!$shippingOrder->canReserveInventory()) {
            throw new \InvalidArgumentException(
                "No se puede crear orden de almacén para una SO en estado '{$shippingOrder->status->label()}'."
            );
        }

        // Get reservations for this SO
        $reservations = $this->reservationService->getReservationsForShippingOrder($shippingOrder);

        if ($reservations->isEmpty()) {
            throw new \InvalidArgumentException(
                'La Shipping Order no tiene reservas de inventario. Reserve inventario primero.'
            );
        }

        // Filter reservations for the specified warehouse
        $warehouseReservations = $reservations->filter(
            fn($r) => $r->inventoryItem->warehouse_id === $warehouse->id
        );

        if ($warehouseReservations->isEmpty()) {
            throw new \InvalidArgumentException(
                "No hay reservas de inventario en el almacén '{$warehouse->name}'."
            );
        }

        return DB::transaction(function () use ($shippingOrder, $warehouse, $warehouseReservations, $reference, $notes) {
            // Create the warehouse order
            $warehouseOrder = WarehouseOrder::create([
                'warehouse_id' => $warehouse->id,
                'shipping_order_id' => $shippingOrder->id,
                'status' => WarehouseOrderStatus::Pending,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            // Create lines from reservations
            foreach ($warehouseReservations as $reservation) {
                $item = $reservation->inventoryItem;

                WarehouseOrderLine::create([
                    'warehouse_order_id' => $warehouseOrder->id,
                    'inventory_item_id' => $item->id,
                    'reservation_id' => $reservation->id,
                    'sku' => $item->item_code,
                    'description' => $item->description,
                    'qty_to_pick' => $reservation->qty_reserved,
                    'qty_picked' => 0,
                    'uom' => $item->uom,
                    'location_code' => $item->location?->code,
                ]);
            }

            Log::info('Warehouse order created from shipping order', [
                'warehouse_order_id' => $warehouseOrder->id,
                'shipping_order_id' => $shippingOrder->id,
                'warehouse_id' => $warehouse->id,
                'lines_count' => $warehouseReservations->count(),
            ]);

            return $warehouseOrder;
        });
    }

    /**
     * Start the picking process for a warehouse order.
     *
     * @param WarehouseOrder $warehouseOrder
     * @return WarehouseOrder
     * @throws \InvalidArgumentException
     */
    public function startPicking(WarehouseOrder $warehouseOrder): WarehouseOrder
    {
        if (!$warehouseOrder->canStartPicking()) {
            throw new \InvalidArgumentException(
                "No se puede iniciar picking para una orden en estado '{$warehouseOrder->status->label()}'."
            );
        }

        $warehouseOrder->update(['status' => WarehouseOrderStatus::Picking]);

        Log::info('Warehouse order picking started', [
            'warehouse_order_id' => $warehouseOrder->id,
        ]);

        return $warehouseOrder;
    }

    /**
     * Update the picked quantity for a warehouse order line.
     *
     * This creates an inventory movement and reduces the inventory item quantity.
     *
     * @param WarehouseOrderLine $line The line to update
     * @param float $qtyPicked New total picked quantity
     * @return WarehouseOrderLine
     * @throws \InvalidArgumentException
     */
    public function updateLinePicked(WarehouseOrderLine $line, float $qtyPicked): WarehouseOrderLine
    {
        $warehouseOrder = $line->warehouseOrder;

        if (!$warehouseOrder->isPicking()) {
            throw new \InvalidArgumentException(
                'Solo se puede actualizar picking cuando la orden está en estado "En Picking".'
            );
        }

        if ($qtyPicked < 0) {
            throw new \InvalidArgumentException('La cantidad picada no puede ser negativa.');
        }

        if ($qtyPicked > $line->qty_to_pick) {
            throw new \InvalidArgumentException(
                "La cantidad picada ({$qtyPicked}) no puede exceder la cantidad a picar ({$line->qty_to_pick})."
            );
        }

        // Calculate the delta (new picks made)
        $previousPicked = (float) $line->qty_picked;
        $delta = $qtyPicked - $previousPicked;

        if ($delta <= 0) {
            // No new picks, just update the line
            $line->update(['qty_picked' => $qtyPicked]);
            return $line;
        }

        return DB::transaction(function () use ($line, $qtyPicked, $delta, $warehouseOrder) {
            $inventoryItem = $line->inventoryItem;

            // Validate enough inventory
            if ($delta > $inventoryItem->qty) {
                throw new \InvalidArgumentException(
                    "No hay suficiente inventario ({$inventoryItem->qty}) para picar ({$delta})."
                );
            }

            // Create inventory movement for the pick
            InventoryMovement::create([
                'inventory_item_id' => $inventoryItem->id,
                'movement_type' => MovementType::Pick,
                'from_location_id' => $inventoryItem->location_id,
                'to_location_id' => null,
                'qty' => -$delta, // Negative because it's outbound
                'reference' => "WO-{$warehouseOrder->id}",
                'user_id' => Auth::id(),
                'notes' => "Picking para orden de almacén #{$warehouseOrder->id}",
            ]);

            // Reduce inventory quantity
            $inventoryItem->decrement('qty', $delta);

            // Update the line
            $line->update(['qty_picked' => $qtyPicked]);

            // Release corresponding reservation if fully picked
            if ($line->isFullyPicked() && $line->reservation_id) {
                $line->reservation?->delete();
            }

            Log::info('Warehouse order line picked', [
                'warehouse_order_id' => $warehouseOrder->id,
                'line_id' => $line->id,
                'sku' => $line->sku,
                'qty_picked' => $qtyPicked,
                'delta' => $delta,
            ]);

            return $line;
        });
    }

    /**
     * Mark a warehouse order as packed.
     *
     * @param WarehouseOrder $warehouseOrder
     * @return WarehouseOrder
     * @throws \InvalidArgumentException
     */
    public function markPacked(WarehouseOrder $warehouseOrder): WarehouseOrder
    {
        if (!$warehouseOrder->canMarkPacked()) {
            throw new \InvalidArgumentException(
                "No se puede marcar como empacado una orden en estado '{$warehouseOrder->status->label()}'."
            );
        }

        // Validate all lines are picked (optional - can be partial)
        // For now, we allow packing even if not fully picked

        $warehouseOrder->update(['status' => WarehouseOrderStatus::Packed]);

        Log::info('Warehouse order marked as packed', [
            'warehouse_order_id' => $warehouseOrder->id,
            'picking_progress' => $warehouseOrder->pickingProgress(),
        ]);

        return $warehouseOrder;
    }

    /**
     * Mark a warehouse order as dispatched.
     *
     * Optionally links to a delivery order for P&D integration.
     *
     * @param WarehouseOrder $warehouseOrder
     * @param int|null $deliveryOrderId Optional delivery order to link
     * @return WarehouseOrder
     * @throws \InvalidArgumentException
     */
    public function markDispatched(WarehouseOrder $warehouseOrder, ?int $deliveryOrderId = null): WarehouseOrder
    {
        if (!$warehouseOrder->canMarkDispatched()) {
            throw new \InvalidArgumentException(
                "No se puede despachar una orden en estado '{$warehouseOrder->status->label()}'."
            );
        }

        return DB::transaction(function () use ($warehouseOrder, $deliveryOrderId) {
            $updateData = ['status' => WarehouseOrderStatus::Dispatched];

            if ($deliveryOrderId) {
                $updateData['delivery_order_id'] = $deliveryOrderId;
            }

            $warehouseOrder->update($updateData);

            // Release any remaining reservations
            foreach ($warehouseOrder->lines as $line) {
                if ($line->reservation_id && $line->reservation) {
                    $line->reservation->delete();
                }
            }

            Log::info('Warehouse order dispatched', [
                'warehouse_order_id' => $warehouseOrder->id,
                'delivery_order_id' => $deliveryOrderId,
            ]);

            return $warehouseOrder;
        });
    }

    /**
     * Cancel a warehouse order.
     *
     * Releases all reservations but does NOT restore picked inventory.
     *
     * @param WarehouseOrder $warehouseOrder
     * @param string|null $reason Cancellation reason
     * @return WarehouseOrder
     * @throws \InvalidArgumentException
     */
    public function cancel(WarehouseOrder $warehouseOrder, ?string $reason = null): WarehouseOrder
    {
        if (!$warehouseOrder->canCancel()) {
            throw new \InvalidArgumentException(
                "No se puede cancelar una orden en estado '{$warehouseOrder->status->label()}'."
            );
        }

        return DB::transaction(function () use ($warehouseOrder, $reason) {
            // Release all reservations
            foreach ($warehouseOrder->lines as $line) {
                if ($line->reservation_id && $line->reservation) {
                    $line->reservation->delete();
                }
            }

            $warehouseOrder->update([
                'status' => WarehouseOrderStatus::Cancelled,
                'notes' => $reason ? ($warehouseOrder->notes . "\nCancelado: " . $reason) : $warehouseOrder->notes,
            ]);

            Log::info('Warehouse order cancelled', [
                'warehouse_order_id' => $warehouseOrder->id,
                'reason' => $reason,
            ]);

            return $warehouseOrder;
        });
    }
}
