<?php

namespace App\Enums;

enum ChargeBasis: string
{
    case PerShipment = 'per_shipment';
    case PerKg = 'per_kg';
    case PerCbm = 'per_cbm';
    case PerContainer = 'per_container';

    public function label(): string
    {
        return match ($this) {
            self::PerShipment => 'Por Envío',
            self::PerKg => 'Por Kg',
            self::PerCbm => 'Por CBM',
            self::PerContainer => 'Por Contenedor',
        };
    }

    public function requiresWeight(): bool
    {
        return $this === self::PerKg;
    }

    public function requiresVolume(): bool
    {
        return $this === self::PerCbm;
    }

    public function isUnitBased(): bool
    {
        return $this === self::PerKg || $this === self::PerCbm;
    }
}
