<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * InventoryReservation - Links inventory items to shipping/sales orders.
 *
 * Represents a quantity of inventory that has been reserved for a specific
 * shipping order or sales order but has not yet been physically picked/moved.
 */
class InventoryReservation extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'inventory_item_id',
        'shipping_order_id',
        'sales_order_id',
        'qty_reserved',
        'created_by',
        'deleted_by',
    ];

    protected $casts = [
        'qty_reserved' => 'decimal:4',
    ];

    /**
     * Get the inventory item this reservation is for.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the shipping order this reservation is linked to.
     */
    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    /**
     * Get the sales order this reservation is linked to.
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * Get the user who created the reservation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who released (deleted) the reservation.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
