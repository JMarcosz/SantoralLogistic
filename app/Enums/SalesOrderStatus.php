<?php

namespace App\Enums;

/**
 * SalesOrder status enum.
 *
 * State transitions:
 * - draft: Initial state, can be edited
 * - confirmed: Order confirmed, inventory reserved
 * - delivering: Delivery in progress
 * - delivered: All items delivered
 * - invoiced: Invoice generated
 * - cancelled: Order cancelled (terminal state)
 */
enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Delivering = 'delivering';
    case Delivered = 'delivered';
    case Invoiced = 'invoiced';
    case Cancelled = 'cancelled';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Confirmed => 'Confirmado',
            self::Delivering => 'En Entrega',
            self::Delivered => 'Entregado',
            self::Invoiced => 'Facturado',
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
            self::Confirmed => 'blue',
            self::Delivering => 'amber',
            self::Delivered => 'emerald',
            self::Invoiced => 'cyan',
            self::Cancelled => 'red',
        };
    }

    /**
     * Check if status is terminal (no more transitions allowed).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Invoiced, self::Cancelled]);
    }

    /**
     * Check if the order is active (can have operational updates).
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Confirmed, self::Delivering, self::Delivered]);
    }

    /**
     * Get valid transitions from this state.
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Delivering, self::Cancelled],
            self::Delivering => [self::Delivered],
            self::Delivered => [self::Invoiced],
            self::Invoiced => [],
            self::Cancelled => [],
        };
    }

    /**
     * Check if transition to a target status is valid.
     */
    public function canTransitionTo(SalesOrderStatus $target): bool
    {
        return in_array($target, $this->validTransitions());
    }
}
