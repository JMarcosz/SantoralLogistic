<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviada',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
            self::Expired => 'Expirada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Sent => 'sky',
            self::Approved => 'emerald',
            self::Rejected => 'red',
            self::Expired => 'amber',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::Draft;
    }

    public function canSend(): bool
    {
        return $this === self::Draft;
    }

    public function canApprove(): bool
    {
        return $this === self::Sent;
    }

    public function canReject(): bool
    {
        return $this === self::Sent;
    }
}
