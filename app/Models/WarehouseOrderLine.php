<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WarehouseOrderLine - Represents a line item in a warehouse order.
 *
 * Each line tracks what inventory item needs to be picked, how much,
 * and the progress of picking.
 */
class WarehouseOrderLine extends Model
{
    protected $fillable = [
        'warehouse_order_id',
        'inventory_item_id',
        'reservation_id',
        'sku',
        'description',
        'qty_to_pick',
        'qty_picked',
        'uom',
        'location_code',
    ];

    protected $casts = [
        'qty_to_pick' => 'decimal:4',
        'qty_picked' => 'decimal:4',
    ];

    // ========== Relationships ==========

    /**
     * Get the warehouse order this line belongs to.
     */
    public function warehouseOrder(): BelongsTo
    {
        return $this->belongsTo(WarehouseOrder::class);
    }

    /**
     * Get the inventory item to pick from.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the reservation this line is linked to (if any).
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class, 'reservation_id');
    }

    // ========== Helpers ==========

    /**
     * Check if this line is fully picked.
     */
    public function isFullyPicked(): bool
    {
        return $this->qty_picked >= $this->qty_to_pick;
    }

    /**
     * Get the remaining quantity to pick.
     */
    public function qtyRemaining(): float
    {
        return max(0, (float) $this->qty_to_pick - (float) $this->qty_picked);
    }

    /**
     * Get the picking progress as a percentage.
     */
    public function pickingProgress(): float
    {
        if ($this->qty_to_pick <= 0) {
            return 100;
        }
        return round(($this->qty_picked / $this->qty_to_pick) * 100, 2);
    }
}
