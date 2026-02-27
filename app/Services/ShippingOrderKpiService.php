<?php

namespace App\Services;

use App\Models\ShippingOrder;

/**
 * Service for calculating Shipping Order KPIs (SLA/OTIF).
 * 
 * Centralized logic for performance indicator calculations.
 */
class ShippingOrderKpiService
{
    /**
     * Recalculate all KPIs for a shipping order.
     * 
     * Updates the order's KPI fields directly without saving.
     * Caller is responsible for persisting changes.
     */
    public function recalculateKpis(ShippingOrder $order): ShippingOrder
    {
        $order->delivered_on_time = $this->calculateOnTime($order);
        $order->delivery_delay_days = $this->calculateDeliveryDelay($order);
        $order->delivered_in_full = $this->calculateInFull($order);

        return $order;
    }

    /**
     * Calculate if delivery was on time.
     * 
     * Returns:
     * - true: actual arrival was on or before planned arrival
     * - false: actual arrival was after planned arrival
     * - null: insufficient data to calculate
     */
    public function calculateOnTime(ShippingOrder $order): ?bool
    {
        if (!$order->actual_arrival_at || !$order->planned_arrival_at) {
            return null;
        }

        return $order->actual_arrival_at->lte($order->planned_arrival_at);
    }

    /**
     * Calculate delivery delay in days.
     * 
     * Returns:
     * - 0: on-time or early delivery
     * - positive integer: days late
     * - null: insufficient data to calculate
     */
    public function calculateDeliveryDelay(ShippingOrder $order): ?int
    {
        if (!$order->actual_arrival_at || !$order->planned_arrival_at) {
            return null;
        }

        $diffInDays = $order->planned_arrival_at->startOfDay()
            ->diffInDays($order->actual_arrival_at->startOfDay(), false);

        // Return 0 for on-time/early, positive for late
        return max(0, $diffInDays);
    }

    /**
     * Calculate if delivery was in full.
     * 
     * NOTE: This is a placeholder for MVP.
     * Full implementation requires line-item level cargo modeling
     * to compare expected vs actual quantities.
     * 
     * Returns: null (not implemented)
     */
    public function calculateInFull(ShippingOrder $order): ?bool
    {
        // Placeholder - requires cargo line-item modeling
        // to compare expected vs actual quantities
        return null;
    }
}
