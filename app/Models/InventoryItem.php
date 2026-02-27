<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'location_id',
        'customer_id',
        'warehouse_receipt_id',
        'warehouse_receipt_line_id',
        'item_code',
        'description',
        'qty',
        'uom',
        'lot_number',
        'serial_number',
        'expiration_date',
        'received_at',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'expiration_date' => 'date',
        'received_at' => 'datetime',
    ];

    /**
     * Get the warehouse this item is stored in.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the location of this item (may be null if not yet put away).
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the customer (owner) of this item.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the warehouse receipt this item came from.
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceipt::class, 'warehouse_receipt_id');
    }

    /**
     * Get the warehouse receipt line this item came from.
     */
    public function warehouseReceiptLine(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceiptLine::class, 'warehouse_receipt_line_id');
    }

    /**
     * Get all movements for this item.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get all reservations for this item.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    /**
     * Get the total reserved quantity for this item.
     */
    public function reservedQuantity(): float
    {
        return (float) $this->reservations()->sum('qty_reserved');
    }

    /**
     * Get the available quantity (total minus reserved).
     */
    public function availableQuantity(): float
    {
        return max(0, (float) $this->qty - $this->reservedQuantity());
    }

    /**
     * Check if the item has any active reservations.
     */
    public function hasReservations(): bool
    {
        return $this->reservations()->exists();
    }

    /**
     * Scope for items by customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope for items by Item Code.
     */
    public function scopeByItemCode($query, string $itemCode)
    {
        return $query->where('item_code', $itemCode);
    }

    /**
     * Scope for items by SKU (alias for byItemCode for backward compatibility).
     * Added as hotfix for InventoryReservationService which uses bySku().
     */
    public function scopeBySku($query, string $sku)
    {
        return $query->where('item_code', $sku);
    }

    /**
     * Scope for items in a specific warehouse.
     */
    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope for items not yet located (pending putaway).
     */
    public function scopePendingPutaway($query)
    {
        return $query->whereNull('location_id');
    }

    /**
     * Scope for items with available quantity.
     */
    public function scopeWithAvailableQty($query)
    {
        return $query->where('qty', '>', 0);
    }

    /**
     * Check if item has been put away.
     */
    public function isLocated(): bool
    {
        return $this->location_id !== null;
    }
}
