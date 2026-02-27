<?php

namespace App\Enums;

enum LocationType: string
{
    case Rack = 'rack';
    case Floor = 'floor';
    case Staging = 'staging';
    case Dock = 'dock';

    public function label(): string
    {
        return match ($this) {
            self::Rack => 'Rack',
            self::Floor => 'Piso',
            self::Staging => 'Área de Preparación',
            self::Dock => 'Muelle',
        };
    }
}
