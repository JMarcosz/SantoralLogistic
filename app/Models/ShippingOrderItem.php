<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_order_id',
        'type', // container, vehicle, loose_cargo
        'identifier', // Container Number, VIN, etc.
        'seal_number',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ShippingOrderItemLine::class);
    }
}
