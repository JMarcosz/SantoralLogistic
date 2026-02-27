<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'license_number',
        'vehicle_plate',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all pickup orders assigned to this driver.
     */
    public function pickupOrders(): HasMany
    {
        return $this->hasMany(PickupOrder::class);
    }

    /**
     * Get all delivery orders assigned to this driver.
     */
    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    /**
     * Scope for active drivers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
