<?php

namespace App\Models;

use App\Enums\WarehouseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WarehouseOrder - Represents a pick/pack/dispatch order in the warehouse.
 *
 * Links a ShippingOrder to physical warehouse operations, managing the
 * picking, packing, and dispatch of inventory items.
 */
class WarehouseOrder extends Model
{
    protected $fillable = [
        'warehouse_id',
        'shipping_order_id',
        'delivery_order_id',
        'status',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => WarehouseOrderStatus::class,
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    // ========== Relationships ==========

    /**
     * Get the warehouse where this order is processed.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the shipping order this warehouse order fulfills.
     */
    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    /**
     * Get the delivery order linked to this warehouse order (if dispatched).
     */
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    /**
     * Get the user who created this order.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all lines for this warehouse order.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseOrderLine::class);
    }

    // ========== State Helpers ==========

    /**
     * Check if the order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === WarehouseOrderStatus::Pending;
    }

    /**
     * Check if picking is in progress.
     */
    public function isPicking(): bool
    {
        return $this->status === WarehouseOrderStatus::Picking;
    }

    /**
     * Check if the order is packed.
     */
    public function isPacked(): bool
    {
        return $this->status === WarehouseOrderStatus::Packed;
    }

    /**
     * Check if the order has been dispatched.
     */
    public function isDispatched(): bool
    {
        return $this->status === WarehouseOrderStatus::Dispatched;
    }

    /**
     * Check if the order is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Check if picking can be started.
     */
    public function canStartPicking(): bool
    {
        return $this->status->canTransitionTo(WarehouseOrderStatus::Picking);
    }

    /**
     * Check if the order can be marked as packed.
     */
    public function canMarkPacked(): bool
    {
        return $this->status->canTransitionTo(WarehouseOrderStatus::Packed);
    }

    /**
     * Check if the order can be dispatched.
     */
    public function canMarkDispatched(): bool
    {
        return $this->status->canTransitionTo(WarehouseOrderStatus::Dispatched);
    }

    /**
     * Check if the order can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status->canTransitionTo(WarehouseOrderStatus::Cancelled);
    }

    // ========== Computed Properties ==========

    /**
     * Get the total quantity to pick across all lines.
     */
    public function totalQtyToPick(): float
    {
        return (float) $this->lines()->sum('qty_to_pick');
    }

    /**
     * Get the total quantity picked across all lines.
     */
    public function totalQtyPicked(): float
    {
        return (float) $this->lines()->sum('qty_picked');
    }

    /**
     * Check if all lines are fully picked.
     */
    public function isFullyPicked(): bool
    {
        return $this->lines()->get()->every(fn($line) => $line->isFullyPicked());
    }

    /**
     * Get the picking progress as a percentage.
     */
    public function pickingProgress(): float
    {
        // Dispatched orders are considered 100% complete
        if ($this->isDispatched()) {
            return 100;
        }

        $total = $this->totalQtyToPick();
        if ($total <= 0) {
            return 100;
        }
        return round(($this->totalQtyPicked() / $total) * 100, 2);
    }

    // ========== Scopes ==========

    /**
     * Scope for orders by status.
     */
    public function scopeOfStatus($query, WarehouseOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', WarehouseOrderStatus::Pending);
    }

    /**
     * Scope for active (non-terminal) orders.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            WarehouseOrderStatus::Dispatched,
            WarehouseOrderStatus::Cancelled,
        ]);
    }
}
