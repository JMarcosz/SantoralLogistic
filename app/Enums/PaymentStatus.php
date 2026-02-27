<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Voided = 'voided';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Posted => 'Contabilizado',
            self::Voided => 'Anulado',
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
            self::Voided => 'red',
        };
    }

    /**
     * Check if payment can be edited.
     */
    public function canEdit(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if payment can be posted.
     */
    public function canPost(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if payment can be voided.
     */
    public function canVoid(): bool
    {
        return $this === self::Posted;
    }

    /**
     * Check if payment can be deleted.
     */
    public function canDelete(): bool
    {
        return $this === self::Draft;
    }
}
