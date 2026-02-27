<?php

namespace App\Enums;

/**
 * Document types for Shipping Orders.
 */
enum DocumentType: string
{
    case AWB = 'AWB';           // Air Waybill
    case BL = 'BL';             // Bill of Lading
    case CI = 'CI';             // Commercial Invoice
    case PL = 'PL';             // Packing List
    case CO = 'CO';             // Certificate of Origin
    case INS = 'INS';           // Insurance Certificate
    case CUST = 'CUST';         // Customs Declaration
    case POD = 'POD';           // Proof of Delivery
    case OTHER = 'OTHER';       // Other documents

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::AWB => 'Air Waybill (AWB)',
            self::BL => 'Bill of Lading (BL)',
            self::CI => 'Commercial Invoice',
            self::PL => 'Packing List',
            self::CO => 'Certificate of Origin',
            self::INS => 'Insurance Certificate',
            self::CUST => 'Customs Declaration',
            self::POD => 'Proof of Delivery',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get short label.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::AWB => 'AWB',
            self::BL => 'BL',
            self::CI => 'Invoice',
            self::PL => 'Packing',
            self::CO => 'Origin',
            self::INS => 'Insurance',
            self::CUST => 'Customs',
            self::POD => 'POD',
            self::OTHER => 'Other',
        };
    }

    /**
     * Get color for badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::AWB => 'blue',
            self::BL => 'indigo',
            self::CI => 'emerald',
            self::PL => 'teal',
            self::CO => 'violet',
            self::INS => 'amber',
            self::CUST => 'orange',
            self::POD => 'green',
            self::OTHER => 'gray',
        };
    }

    /**
     * Get all types as options for selects.
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ])->toArray();
    }
}
