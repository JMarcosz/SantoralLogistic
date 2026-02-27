<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\ShippingOrderStatus;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\ShippingOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing inventory reservations linked to Shipping Orders.
 *
 * Handles the reservation of inventory items for outbound shipments without
 * physically moving the inventory. This creates a separation between
 * "total quantity" and "available quantity".
 */
class InventoryReservationService
{
    /**
     * Reserve inventory for a shipping order.
     *
     * @param ShippingOrder $shippingOrder The shipping order to reserve for
     * @param array $lines Array of reservation lines, each containing:
     *                     - sku: string (required)
     *                     - qty: float (required)
     *                     - warehouse_id: int (optional, uses customer's default if not specified)
     * @return array Created reservations
     * @throws \InvalidArgumentException When validation fails
     */
    public function reserveForShippingOrder(ShippingOrder $shippingOrder, array $lines): array
    {
        // Validate SO status
        if (!$shippingOrder->canReserveInventory()) {
            throw new \InvalidArgumentException(
                "No se puede reservar inventario para una orden en estado '{$shippingOrder->status->label()}'."
            );
        }

        if (empty($lines)) {
            throw new \InvalidArgumentException('Debe especificar al menos una línea de reserva.');
        }

        return DB::transaction(function () use ($shippingOrder, $lines) {
            $createdReservations = [];
            $customerId = $shippingOrder->customer_id;
            $userId = auth()->id();

            foreach ($lines as $index => $line) {
                $sku = $line['sku'] ?? null;
                $qty = (float) ($line['qty'] ?? 0);
                $warehouseId = $line['warehouse_id'] ?? null;

                // Validate line
                if (empty($sku)) {
                    throw new \InvalidArgumentException("Línea {$index}: SKU es requerido.");
                }
                if ($qty <= 0) {
                    throw new \InvalidArgumentException("Línea {$index}: Cantidad debe ser mayor a 0.");
                }

                // Find inventory items matching criteria with LOCK
                $items = $this->findAvailableInventoryForUpdate($customerId, $sku, $warehouseId);

                if ($items->isEmpty()) {
                    throw new \InvalidArgumentException(
                        "Línea {$index}: No hay inventario disponible para SKU '{$sku}'."
                    );
                }

                // Calculate total available using eager loaded sums
                $totalAvailable = $items->sum(fn($item) => max(0, $item->qty - ($item->reserved_qty_sum ?? 0)));

                if ($totalAvailable < $qty) {
                    throw new \InvalidArgumentException(
                        "Línea {$index}: Cantidad solicitada ({$qty}) excede disponible ({$totalAvailable}) para SKU '{$sku}'."
                    );
                }

                // Reserve from items (FIFO - oldest first based on received_at)
                $remainingToReserve = $qty;

                foreach ($items as $item) {
                    if ($remainingToReserve <= 0) {
                        break;
                    }

                    // Use pre-calculated sum from eager load
                    $reserved = $item->reserved_qty_sum ?? 0;
                    $available = max(0, $item->qty - $reserved);

                    if ($available <= 0) {
                        continue;
                    }

                    $toReserve = min($available, $remainingToReserve);

                    $reservation = InventoryReservation::create([
                        'inventory_item_id' => $item->id,
                        'shipping_order_id' => $shippingOrder->id,
                        'qty_reserved' => $toReserve,
                        'created_by' => $userId,
                    ]);

                    $createdReservations[] = $reservation;
                    $remainingToReserve -= $toReserve;

                    // Update the reserved_qty_sum for subsequent iterations if we hit the same item again (unlikely in this loop logic but good practice)
                    $item->reserved_qty_sum = $reserved + $toReserve;

                    Log::info('Inventory reserved', [
                        'reservation_id' => $reservation->id,
                        'inventory_item_id' => $item->id,
                        'shipping_order_id' => $shippingOrder->id,
                        'sku' => $sku,
                        'qty_reserved' => $toReserve,
                        'user_id' => $userId,
                    ]);

                    // Create movement record for traceability
                    InventoryMovement::create([
                        'inventory_item_id' => $item->id,
                        'movement_type' => MovementType::Reserve,
                        'qty' => $toReserve,
                        'from_location_id' => $item->location_id,
                        'to_location_id' => null,
                        'reference' => "SO:{$shippingOrder->order_number}",
                        'notes' => "Reserva para orden de envío {$shippingOrder->order_number}",
                        'user_id' => $userId,
                    ]);
                }
            }

            return $createdReservations;
        });
    }

    /**
     * Release all reservations for a shipping order.
     *
     * @param ShippingOrder $shippingOrder The shipping order to release reservations for
     * @return int Number of reservations deleted
     */
    public function releaseReservationsForShippingOrder(ShippingOrder $shippingOrder): int
    {
        $reservations = $shippingOrder->inventoryReservations()->get();
        $count = 0;
        $userId = auth()->id();

        foreach ($reservations as $reservation) {
            $reservation->deleted_by = $userId;
            $reservation->save(); // Save the deleted_by user before soft delete
            if ($reservation->delete()) {
                $count++;

                // Create movement record for traceability
                InventoryMovement::create([
                    'inventory_item_id' => $reservation->inventory_item_id,
                    'movement_type' => MovementType::Release,
                    'qty' => $reservation->qty_reserved,
                    'from_location_id' => null,
                    'to_location_id' => $reservation->inventoryItem->location_id ?? null,
                    'reference' => "SO:{$shippingOrder->order_number}",
                    'notes' => "Liberación de reserva para orden de envío {$shippingOrder->order_number}",
                    'user_id' => $userId,
                ]);
            }
        }

        if ($count > 0) {
            Log::info('Inventory reservations released', [
                'shipping_order_id' => $shippingOrder->id,
                'count' => $count,
                'user_id' => $userId,
            ]);
        }

        return $count;
    }

    /**
     * Release a specific reservation.
     *
     * @param InventoryReservation $reservation The reservation to release
     * @return bool True if deleted
     */
    public function releaseReservation(InventoryReservation $reservation): bool
    {
        $userId = auth()->id();
        $reservation->deleted_by = $userId;
        $reservation->save();

        // Load shipping order for reference
        $reservation->load('shippingOrder', 'inventoryItem');

        Log::info('Inventory reservation released', [
            'reservation_id' => $reservation->id,
            'inventory_item_id' => $reservation->inventory_item_id,
            'shipping_order_id' => $reservation->shipping_order_id,
            'qty_reserved' => $reservation->qty_reserved,
            'user_id' => $userId,
        ]);

        $deleted = $reservation->delete();

        if ($deleted) {
            // Create movement record for traceability
            InventoryMovement::create([
                'inventory_item_id' => $reservation->inventory_item_id,
                'movement_type' => MovementType::Release,
                'qty' => $reservation->qty_reserved,
                'from_location_id' => null,
                'to_location_id' => $reservation->inventoryItem->location_id ?? null,
                'reference' => "SO:{$reservation->shippingOrder->order_number}",
                'notes' => "Liberación de reserva individual",
                'user_id' => $userId,
            ]);
        }

        return $deleted;
    }

    /**
     * Find inventory items with available quantity (Read only).
     *
     * @param int $customerId Customer ID (owner of inventory)
     * @param string $sku SKU to search for
     * @param int|null $warehouseId Optional warehouse filter
     * @return Collection<InventoryItem>
     */
    public function findAvailableInventory(int $customerId, string $sku, ?int $warehouseId = null): Collection
    {
        $query = InventoryItem::query()
            ->forCustomer($customerId)
            ->bySku($sku)
            ->withAvailableQty()
            ->withSum('reservations as reserved_qty_sum', 'qty_reserved')
            ->orderBy('received_at', 'asc');

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query->get();
    }

    /**
     * Find inventory items for update (Locking).
     * Only for use within transaction.
     */
    protected function findAvailableInventoryForUpdate(int $customerId, string $sku, ?int $warehouseId = null): Collection
    {
        $query = InventoryItem::query()
            ->forCustomer($customerId)
            ->bySku($sku)
            ->where('qty', '>', 0)
            ->withSum('reservations as reserved_qty_sum', 'qty_reserved')
            ->lockForUpdate() // Lock rows
            ->orderBy('received_at', 'asc');

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query->get();
    }

    /**
     * Get all reservations for a shipping order with related inventory items.
     *
     * @param ShippingOrder $shippingOrder
     * @return Collection<InventoryReservation>
     */
    public function getReservationsForShippingOrder(ShippingOrder $shippingOrder): Collection
    {
        return $shippingOrder->inventoryReservations()
            ->with(['inventoryItem.warehouse', 'inventoryItem.location'])
            ->get();
    }

    /**
     * Check if a quantity reduction would cause over-reservation.
     *
     * @param InventoryItem $item The inventory item
     * @param float $newQty The new quantity after reduction
     * @return bool True if it would cause over-reservation
     */
    public function wouldCauseOverReservation(InventoryItem $item, float $newQty): bool
    {
        $reservedQty = $item->reservedQuantity();
        return $newQty < $reservedQty;
    }
}
