<?php

namespace App\Enums;

enum PaymentType: string
{
    case Inbound = 'inbound';   // Customer payment (AR)
    case Outbound = 'outbound'; // Supplier payment (AP)

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Inbound => 'Cobro',
            self::Outbound => 'Pago',
        };
    }

    /**
     * Get icon name.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Inbound => 'arrow-down-left',
            self::Outbound => 'arrow-up-right',
        };
    }

    /**
     * Get color for UI.
     */
    public function color(): string
    {
        return match ($this) {
            self::Inbound => 'emerald',
            self::Outbound => 'blue',
        };
    }
}
