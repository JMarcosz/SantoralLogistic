<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all locations in this warehouse.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get all warehouse receipts for this warehouse.
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(WarehouseReceipt::class);
    }

    /**
     * Get all inventory items in this warehouse.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Scope for active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
