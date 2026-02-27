<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingOrderItemLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_order_item_id',
        'pieces',
        'description',
        'weight_kg',
        'volume_cbm',
        'marks_numbers',
        'hs_code',
    ];

    protected $casts = [
        'pieces' => 'integer',
        'weight_kg' => 'decimal:3',
        'volume_cbm' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ShippingOrderItem::class, 'shipping_order_item_id');
    }
}
