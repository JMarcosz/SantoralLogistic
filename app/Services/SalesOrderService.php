<?php

namespace App\Services;

use App\Enums\SalesOrderStatus;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\InventoryMovement;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Enums\MovementType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        private InventoryReservationService $reservationService
    ) {}

    /**
     * Create a SalesOrder from an approved Quote.
     * Copies quote lines preserving line_type.
     */
    public function createFromQuote(Quote $quote): SalesOrder
    {
        if ($quote->hasSalesOrder()) {
            throw new \RuntimeException('Esta cotización ya tiene una orden de pedido asociada.');
        }

        return DB::transaction(function () use ($quote) {
            $order = SalesOrder::create([
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'contact_id' => $quote->contact_id,
                'currency_id' => $quote->currency_id,
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'total_amount' => $quote->total_amount,
                'notes' => $quote->notes,
                'created_by' => Auth::id(),
            ]);

            foreach ($quote->lines as $quoteLine) {
                SalesOrderLine::create([
                    'sales_order_id' => $order->id,
                    'product_service_id' => $quoteLine->product_service_id,
                    'line_type' => $quoteLine->line_type ?? 'service',
                    'description' => $quoteLine->description,
                    'quantity' => $quoteLine->quantity,
                    'unit_price' => $quoteLine->unit_price,
                    'unit_cost' => $quoteLine->unit_cost ?? 0,
                    'discount_percent' => $quoteLine->discount_percent,
                    'tax_rate' => $quoteLine->tax_rate,
                    'line_total' => $quoteLine->line_total,
                    'sort_order' => $quoteLine->sort_order,
                ]);
            }

            return $order;
        });
    }

    /**
     * Confirm a sales order and auto-reserve inventory for product lines.
     * Returns warnings if stock is insufficient (does NOT block confirmation).
     *
     * @return array{order: SalesOrder, warnings: string[]}
     */
    public function confirm(SalesOrder $order): array
    {
        if (!$order->canConfirm()) {
            throw new \RuntimeException('Solo se pueden confirmar órdenes en estado borrador.');
        }

        $warnings = [];

        DB::transaction(function () use ($order, &$warnings) {
            // Transition status
            $order->status = SalesOrderStatus::Confirmed;
            $order->confirmed_at = now();
            $order->save();

            // Auto-reserve inventory for product lines
            $productLines = $order->productLines()->with('productService')->get();

            foreach ($productLines as $line) {
                try {
                    $this->reserveInventoryForLine($order, $line);
                } catch (\RuntimeException $e) {
                    $warnings[] = "{$line->productService->code}: {$e->getMessage()}";
                }
            }
        });

        return [
            'order' => $order->fresh(),
            'warnings' => $warnings,
        ];
    }

    /**
     * Reserve inventory for a single product line using FIFO.
     */
    private function reserveInventoryForLine(SalesOrder $order, SalesOrderLine $line): void
    {
        $customerId = $order->customer_id;
        $productServiceId = $line->product_service_id;
        $requiredQty = (float) $line->quantity;

        // Find available inventory items by product_service_id and customer (FIFO)
        $availableItems = InventoryItem::where('customer_id', $customerId)
            ->where('product_service_id', $productServiceId)
            ->where('qty', '>', 0)
            ->orderBy('received_at', 'asc')
            ->lockForUpdate()
            ->get();

        $remainingQty = $requiredQty;

        foreach ($availableItems as $item) {
            if ($remainingQty <= 0) {
                break;
            }

            $availableQty = $item->availableQuantity();
            if ($availableQty <= 0) {
                continue;
            }

            $toReserve = min($availableQty, $remainingQty);

            InventoryReservation::create([
                'inventory_item_id' => $item->id,
                'sales_order_id' => $order->id,
                'qty_reserved' => $toReserve,
                'created_by' => Auth::id(),
            ]);

            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'movement_type' => MovementType::Reserve,
                'qty' => $toReserve,
                'reference' => $order->order_number,
                'user_id' => Auth::id(),
                'notes' => "Reserva para pedido {$order->order_number}",
            ]);

            $remainingQty -= $toReserve;
        }

        if ($remainingQty > 0) {
            $productCode = $line->productService->code ?? 'N/A';
            throw new \RuntimeException(
                "Stock insuficiente para {$productCode}. Faltan {$remainingQty} {$line->productService->uom}."
            );
        }
    }

    /**
     * Mark a sales order as delivering.
     */
    public function startDelivery(SalesOrder $order): SalesOrder
    {
        if (!$order->canDeliver()) {
            throw new \RuntimeException('Solo se pueden despachar órdenes confirmadas.');
        }

        $order->status = SalesOrderStatus::Delivering;
        $order->save();

        return $order;
    }

    /**
     * Mark a sales order as delivered and deduct physical inventory.
     */
    public function markDelivered(SalesOrder $order): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::Delivering) {
            throw new \RuntimeException('Solo se pueden marcar como entregadas órdenes en estado "En Entrega".');
        }

        DB::transaction(function () use ($order) {
            // Deduct inventory for each reservation
            $reservations = $order->inventoryReservations()->with('inventoryItem')->get();

            foreach ($reservations as $reservation) {
                $item = $reservation->inventoryItem;

                // Deduct qty from inventory
                $item->qty = max(0, (float) $item->qty - (float) $reservation->qty_reserved);
                $item->save();

                // Record pick movement
                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'movement_type' => MovementType::Pick,
                    'from_location_id' => $item->location_id,
                    'qty' => $reservation->qty_reserved,
                    'reference' => $order->order_number,
                    'user_id' => Auth::id(),
                    'notes' => "Entrega pedido {$order->order_number}",
                ]);
            }

            $order->status = SalesOrderStatus::Delivered;
            $order->delivered_at = now();
            $order->save();
        });

        return $order;
    }
}
