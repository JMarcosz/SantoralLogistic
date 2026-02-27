<?php

namespace App\Enums;

enum MovementType: string
{
    case Receive = 'receive';
    case Putaway = 'putaway';
    case Pick = 'pick';
    case Transfer = 'transfer';
    case Adjust = 'adjust';
    case Return = 'return';
    case Reserve = 'reserve';
    case Release = 'release';

    public function label(): string
    {
        return match ($this) {
            self::Receive => 'Recepción',
            self::Putaway => 'Ubicación',
            self::Pick => 'Picking',
            self::Transfer => 'Transferencia',
            self::Adjust => 'Ajuste',
            self::Return => 'Devolución',
            self::Reserve => 'Reserva',
            self::Release => 'Liberación',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [self::Receive, self::Return, self::Release]);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [self::Pick, self::Reserve]);
    }
}
