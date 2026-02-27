<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CycleCountLine - Represents a line item in a cycle count.
 *
 * Tracks the expected quantity vs the counted quantity for each
 * inventory item, calculating differences for reconciliation.
 */
class CycleCountLine extends Model
{
    protected $fillable = [
        'cycle_count_id',
        'inventory_item_id',
        'expected_qty',
        'counted_qty',
        'difference_qty',
        'counted_at',
    ];

    protected $casts = [
        'expected_qty' => 'decimal:4',
        'counted_qty' => 'decimal:4',
        'difference_qty' => 'decimal:4',
        'counted_at' => 'datetime',
    ];

    // ========== Relationships ==========

    /**
     * Get the cycle count this line belongs to.
     */
    public function cycleCount(): BelongsTo
    {
        return $this->belongsTo(CycleCount::class);
    }

    /**
     * Get the inventory item being counted.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    // ========== Helpers ==========

    /**
     * Check if this line has been counted.
     */
    public function isCounted(): bool
    {
        return $this->counted_qty !== null;
    }

    /**
     * Check if there is a difference.
     */
    public function hasDifference(): bool
    {
        return $this->difference_qty !== null && $this->difference_qty != 0;
    }

    /**
     * Get the difference type (positive, negative, or none).
     */
    public function differenceType(): string
    {
        if (!$this->hasDifference()) {
            return 'none';
        }
        return $this->difference_qty > 0 ? 'surplus' : 'shortage';
    }
}
