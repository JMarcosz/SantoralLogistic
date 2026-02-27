<?php

namespace App\Enums;

/**
 * Status values for PickupOrder.
 *
 * State Machine Flow:
 * Pending → Assigned → InProgress → Completed
 *                              ↘ Cancelled
 */
enum PickupOrderStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get human-readable label for status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Assigned => 'Asignado',
            self::InProgress => 'En Progreso',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Get color class for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Assigned => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'destructive',
        };
    }
}
