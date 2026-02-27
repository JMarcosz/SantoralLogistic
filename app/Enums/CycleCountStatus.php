<?php

namespace App\Enums;

/**
 * CycleCount status enum.
 *
 * State transitions:
 * - draft: Created, not yet started
 * - in_progress: Counting in progress
 * - completed: Finished and reconciled (terminal)
 * - cancelled: Cancelled (terminal)
 */
enum CycleCountStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::InProgress => 'En Progreso',
            self::Completed => 'Completado',
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
            self::InProgress => 'amber',
            self::Completed => 'emerald',
            self::Cancelled => 'red',
        };
    }

    /**
     * Check if status is terminal.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled]);
    }

    /**
     * Get valid transitions from this state.
     */
    public function validTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed => [],
            self::Cancelled => [],
        };
    }

    /**
     * Check if transition to a target status is valid.
     */
    public function canTransitionTo(CycleCountStatus $target): bool
    {
        return in_array($target, $this->validTransitions());
    }
}
