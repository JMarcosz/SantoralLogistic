<?php

namespace App\Enums;

/**
 * WarehouseOrder status enum.
 *
 * State transitions:
 * - pending: Created, awaiting picking
 * - picking: Picking in progress
 * - packed: Items packed, ready for dispatch
 * - dispatched: Shipped out (terminal)
 * - cancelled: Order cancelled (terminal)
 */
enum WarehouseOrderStatus: string
{
    case Pending = 'pending';
    case Picking = 'picking';
    case Packed = 'packed';
    case Dispatched = 'dispatched';
    case Cancelled = 'cancelled';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Picking => 'En Picking',
            self::Packed => 'Empacado',
            self::Dispatched => 'Despachado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Get color/style for badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'slate',
            self::Picking => 'amber',
            self::Packed => 'blue',
            self::Dispatched => 'emerald',
            self::Cancelled => 'red',
        };
    }

    /**
     * Check if status is terminal (no more transitions allowed).
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Dispatched, self::Cancelled]);
    }

    /**
     * Get valid transitions from this state.
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Picking, self::Cancelled],
            self::Picking => [self::Packed, self::Cancelled],
            self::Packed => [self::Dispatched, self::Cancelled],
            self::Dispatched => [],
            self::Cancelled => [],
        };
    }

    /**
     * Check if transition to a target status is valid.
     */
    public function canTransitionTo(WarehouseOrderStatus $target): bool
    {
        return in_array($target, $this->validTransitions());
    }
}
