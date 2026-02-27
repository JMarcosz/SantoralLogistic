<?php

namespace App\Services;

use App\Enums\ShippingOrderStatus;
use App\Models\Charge;
use App\Models\ShippingOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShippingOrderChargeService
{
    /**
     * Create a new charge for a shipping order.
     */
    public function createCharge(ShippingOrder $order, array $data): Charge
    {
        $this->ensureOrderEditable($order);

        return DB::transaction(function () use ($order, $data) {
            $data['amount'] = $this->calculateAmount($data['unit_price'], $data['qty']);
            $data['is_manual'] = true; // Mark as manually created from UI

            // Auto-assign sort_order
            $maxSortOrder = $order->charges()->max('sort_order') ?? 0;
            $data['sort_order'] = $maxSortOrder + 1;

            return $order->charges()->create($data);
        });
    }

    /**
     * Update an existing charge.
     */
    public function updateCharge(Charge $charge, array $data): Charge
    {
        $this->ensureOrderEditable($charge->shippingOrder);

        return DB::transaction(function () use ($charge, $data) {
            if (isset($data['unit_price']) || isset($data['qty'])) {
                $unitPrice = $data['unit_price'] ?? $charge->unit_price;
                $qty = $data['qty'] ?? $charge->qty;
                $data['amount'] = $this->calculateAmount($unitPrice, $qty);
            }

            $charge->update($data);
            return $charge->fresh();
        });
    }

    /**
     * Delete a charge.
     */
    public function deleteCharge(Charge $charge): void
    {
        $this->ensureOrderEditable($charge->shippingOrder);
        $charge->delete();
    }

    /**
     * Validate that the order allows charge modifications.
     */
    protected function ensureOrderEditable(ShippingOrder $order): void
    {
        // Cannot modify charges if order is cancelled
        if ($order->status === ShippingOrderStatus::Cancelled) {
            throw new InvalidArgumentException('No se pueden modificar cargos de una orden cancelada.');
        }

        // Cannot modify charges if order is closed
        if ($order->status === ShippingOrderStatus::Closed) {
            throw new InvalidArgumentException('No se pueden modificar cargos de una orden cerrada.');
        }

        // Cannot modify charges if there's an active (non-cancelled) pre-invoice
        if ($order->preInvoices()->where('status', '!=', 'cancelled')->exists()) {
            throw new InvalidArgumentException(
                'No se pueden modificar cargos cuando existe una pre-factura activa. ' .
                    'Cancele la pre-factura primero si necesita hacer cambios.'
            );
        }
    }

    /**
     * Calculate total amount.
     */
    protected function calculateAmount(float $unitPrice, float $qty): float
    {
        return round($unitPrice * $qty, 4);
    }
}
