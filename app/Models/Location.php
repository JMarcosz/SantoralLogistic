<?php

namespace App\Models;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'code',
        'zone',
        'type',
        'is_active',
        'max_weight_kg',
    ];

    protected $casts = [
        'type' => LocationType::class,
        'is_active' => 'boolean',
        'max_weight_kg' => 'decimal:2',
    ];

    /**
     * Get the warehouse this location belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get all inventory items in this location.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Scope for active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get full location path (warehouse code + location code).
     */
    public function getFullCodeAttribute(): string
    {
        return $this->warehouse?->code . '/' . $this->code;
    }
}
