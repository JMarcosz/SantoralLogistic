<?php

namespace App\Enums;

enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Posted => 'Contabilizado',
            self::Reversed => 'Reversado',
        };
    }

    /**
     * Get color for UI badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Posted => 'emerald',
            self::Reversed => 'red',
        };
    }

    /**
     * Check if entry can be edited.
     */
    public function canEdit(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if entry can be posted.
     */
    public function canPost(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if entry can be reversed.
     */
    public function canReverse(): bool
    {
        return $this === self::Posted;
    }

    /**
     * Check if entry can be deleted.
     */
    public function canDelete(): bool
    {
        return $this === self::Draft;
    }
}
