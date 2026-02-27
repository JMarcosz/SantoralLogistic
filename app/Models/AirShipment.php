<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AirShipment extends Model
{
    protected $fillable = [
        'shipping_order_id',
        'mawb_number',
        'hawb_number',
        'airline_name',
        'flight_number',
    ];

    /**
     * Get the shipping order that owns this air shipment.
     */
    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }
}
