<?php

namespace App\Services;

use App\Enums\MilestoneCode;
use App\Enums\ShippingOrderStatus;
use App\Exceptions\InvalidShippingOrderStateTransitionException;
use App\Models\ShippingOrder;
use Illuminate\Support\Facades\DB;

/**
 * State Machine for Shipping Orders.
 * 
 * Centralized logic for all status transitions.
 * 
 * State Flow:
 *   draft → booked → in_transit → arrived → delivered → closed
 *                ↘      ↘
 *                 cancelled (from draft or booked only)
 * 
 * Terminal states: closed, cancelled (no transitions allowed)
 */
class ShippingOrderStateMachine
{
    public function __construct(
        protected ?ShippingOrderKpiService $kpiService = null,
    ) {
        // Allow null for backwards compatibility in tests
        $this->kpiService = $kpiService ?? new ShippingOrderKpiService();
    }

    /**
     * Book the shipping order (carrier confirmed).
     * Captures terms snapshot for legal traceability.
     * 
     * @throws InvalidShippingOrderStateTransitionException
     */
    public function book(ShippingOrder $order): ShippingOrder
    {
        return $this->transition(
            $order,
            ShippingOrderStatus::Booked,
            function ($order) {
                // Capture terms snapshot before transition
                $termsResolver = app(TermsResolverService::class);
                $termsResolver->captureShippingOrderSnapshots($order);
            },
            MilestoneCode::Booked
        );
    }

    /**
     * Start transit (shipment departed).
     * 
     * @throws InvalidShippingOrderStateTransitionException
     */
    public function startTransit(ShippingOrder $order, ?\DateTimeInterface $departureAt = null): ShippingOrder
    {
        return $this->transition(
            $order,
            ShippingOrderStatus::InTransit,
            function ($order) use ($departureAt) {
                $order->actual_departure_at = $departureAt ?? now();
            },
            MilestoneCode::DepartedOrigin
        );
    }

    /**
     * Mark as arrived at destination.
     * 
     * @throws InvalidShippingOrderStateTransitionException
     */
    public function arrive(ShippingOrder $order, ?\DateTimeInterface $arrivalAt = null): ShippingOrder
    {
        return $this->transition(
            $order,
            ShippingOrderStatus::Arrived,
            function ($order) use ($arrivalAt) {
                $order->actual_arrival_at = $arrivalAt ?? now();
                // Calculate KPIs on arrival (preview before final delivery)
                $this->kpiService->recalculateKpis($order);
            },
            MilestoneCode::ArrivedDestination
        );
    }

    /**
     * Mark as delivered to final destination.
     * 
     * @throws InvalidShippingOrderStateTransitionException
     */
    public function deliver(ShippingOrder $order): ShippingOrder
    {
        return $this->transition(
            $order,
            ShippingOrderStatus::Delivered,
            function ($order) {
                $order->delivery_date = now()->toDateString();
                // Finalize KPI calculation on delivery
                $this->kpiService->recalculateKpis($order);
            },
            MilestoneCode::Delivered
        );
    }

    /**
     * Close the order (completed).
     * 
     * @throws InvalidShippingOrderStateTransitionException
     */
    public function close(ShippingOrder $order): ShippingOrder
    {
        return $this->transition($order, ShippingOrderStatus::Closed);
    }

    /**
     * Cancel the order.
     * 
     * @throws InvalidShippingOrderStateTransitionException
     */
    public function cancel(ShippingOrder $order, ?string $reason = null): ShippingOrder
    {
        return $this->transition(
            $order,
            ShippingOrderStatus::Cancelled,
            function ($order) use ($reason) {
                $order->is_active = false;
                if ($reason && $order->notes) {
                    $order->notes .= "\n\n[CANCELADO]: {$reason}";
                } elseif ($reason) {
                    $order->notes = "[CANCELADO]: {$reason}";
                }
            },
            MilestoneCode::Cancelled,
            $reason
        );
    }

    /**
     * Perform a status transition with validation.
     * 
     * @throws InvalidShippingOrderStateTransitionException
     */
    protected function transition(
        ShippingOrder $order,
        ShippingOrderStatus $targetStatus,
        ?callable $beforeSave = null,
        ?MilestoneCode $milestoneCode = null,
        ?string $milestoneRemarks = null
    ): ShippingOrder {
        // Check if already in terminal state
        if ($order->status->isTerminal()) {
            throw InvalidShippingOrderStateTransitionException::alreadyTerminal($order->status);
        }

        // Validate transition
        if (!$order->status->canTransitionTo($targetStatus)) {
            throw $this->createTransitionException($order->status, $targetStatus);
        }

        return DB::transaction(function () use ($order, $targetStatus, $beforeSave, $milestoneCode, $milestoneRemarks) {
            // Apply any pre-save modifications
            if ($beforeSave) {
                $beforeSave($order);
            }

            // Update status
            $order->status = $targetStatus;
            $order->save();

            // Create milestone if specified
            if ($milestoneCode) {
                $order->addMilestone(
                    code: $milestoneCode,
                    remarks: $milestoneRemarks
                );
            }

            return $order->fresh();
        });
    }

    /**
     * Create appropriate exception for the failed transition.
     */
    protected function createTransitionException(
        ShippingOrderStatus $from,
        ShippingOrderStatus $to
    ): InvalidShippingOrderStateTransitionException {
        return match ($to) {
            ShippingOrderStatus::Booked => InvalidShippingOrderStateTransitionException::cannotBook($from),
            ShippingOrderStatus::InTransit => InvalidShippingOrderStateTransitionException::cannotStartTransit($from),
            ShippingOrderStatus::Arrived => InvalidShippingOrderStateTransitionException::cannotArrive($from),
            ShippingOrderStatus::Delivered => InvalidShippingOrderStateTransitionException::cannotDeliver($from),
            ShippingOrderStatus::Closed => InvalidShippingOrderStateTransitionException::cannotClose($from),
            ShippingOrderStatus::Cancelled => InvalidShippingOrderStateTransitionException::cannotCancel($from),
            default => new InvalidShippingOrderStateTransitionException($from, $to),
        };
    }
}
