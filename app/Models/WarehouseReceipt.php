<?php

namespace App\Models;

use App\Enums\WarehouseReceiptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'customer_id',
        'shipping_order_id',
        'receipt_number',
        'reference',
        'status',
        'expected_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'status' => WarehouseReceiptStatus::class,
        'expected_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    /**
     * Get the warehouse this receipt belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the customer (owner) of this receipt.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get all inventory items from this receipt.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get all lines of this receipt.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(WarehouseReceiptLine::class);
    }

    /**
     * Get the linked shipping order (optional).
     */
    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    /**
     * Scope for draft receipts.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', WarehouseReceiptStatus::Draft);
    }

    /**
     * Scope for received receipts.
     */
    public function scopeReceived($query)
    {
        return $query->where('status', WarehouseReceiptStatus::Received);
    }

    /**
     * Check if receipt can be modified.
     */
    public function isEditable(): bool
    {
        return $this->status === WarehouseReceiptStatus::Draft;
    }
}
