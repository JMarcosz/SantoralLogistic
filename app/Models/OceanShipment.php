<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OceanShipment extends Model
{
    protected $fillable = [
        'shipping_order_id',
        'mbl_number',
        'hbl_number',
        'carrier_name',
        'vessel_name',
        'voyage_number',
        'container_details',
    ];

    protected $casts = [
        'container_details' => 'array',
    ];

    /**
     * Get the shipping order that owns this ocean shipment.
     */
    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }
}
