<?php

namespace App\Enums;

/**
 * ShippingOrder status enum.
 * 
 * State transitions:
 * - draft: Initial state, can be edited
 * - booked: Carrier confirmed, shipment scheduled
 * - in_transit: Shipment departed from origin
 * - arrived: Shipment arrived at destination port/warehouse
 * - delivered: Delivered to final destination
 * - closed: Shipment completed and closed
 * - cancelled: Shipment cancelled (terminal state)
 */
enum ShippingOrderStatus: string
{
    case Draft = 'draft';
    case Booked = 'booked';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Delivered = 'delivered';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Booked => 'Reservado',
            self::InTransit => 'En Tránsito',
            self::Arrived => 'Llegado',
            self::Delivered => 'Entregado',
            self::Closed => 'Cerrado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Get color/style for badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Booked => 'blue',
            self::InTransit => 'amber',
            self::Arrived => 'cyan',
            self::Delivered => 'emerald',
            self::Closed => 'gray',
            self::Cancelled => 'red',
        };
    }

    /**
     * Check if status is terminal (no more transitions allowed).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled]);
    }

    /**
     * Check if the shipment is active (can have operational updates).
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Booked, self::InTransit, self::Arrived, self::Delivered]);
    }

    /**
     * Get valid transitions from this state.
     * This prepares for a future state machine implementation.
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Booked, self::Cancelled],
            self::Booked => [self::InTransit, self::Cancelled],
            self::InTransit => [self::Arrived],
            self::Arrived => [self::Delivered],
            self::Delivered => [self::Closed],
            self::Closed => [],
            self::Cancelled => [],
        };
    }

    /**
     * Check if transition to a target status is valid.
     */
    public function canTransitionTo(ShippingOrderStatus $target): bool
    {
        return in_array($target, $this->validTransitions());
    }
}
