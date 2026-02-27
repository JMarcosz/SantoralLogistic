<?php

namespace App\Enums;

enum WarehouseReceiptStatus: string
{
    case Draft = 'draft';
    case Received = 'received';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Received => 'Recibido',
            self::Closed => 'Cerrado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-800',
            self::Received => 'bg-blue-100 text-blue-800',
            self::Closed => 'bg-green-100 text-green-800',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }
}
