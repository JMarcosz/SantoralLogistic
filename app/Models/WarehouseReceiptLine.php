<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_receipt_id',
        'product_service_id',
        'item_code',
        'description',
        'expected_qty',
        'received_qty',
        'uom',
        'lot_number',
        'serial_number',
        'expiration_date',
    ];

    protected $casts = [
        'expected_qty' => 'decimal:3',
        'received_qty' => 'decimal:3',
        'expiration_date' => 'date',
    ];

    /**
     * Get the warehouse receipt this line belongs to.
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(WarehouseReceipt::class, 'warehouse_receipt_id');
    }

    /**
     * Get the product/service linked to this receipt line.
     */
    public function productService(): BelongsTo
    {
        return $this->belongsTo(ProductService::class);
    }

    /**
     * Check if expected qty matches received qty.
     */
    public function isFullyReceived(): bool
    {
        if ($this->expected_qty === null) {
            return true;
        }
        return $this->received_qty >= $this->expected_qty;
    }

    /**
     * Get variance between expected and received.
     */
    public function getVarianceAttribute(): ?float
    {
        if ($this->expected_qty === null) {
            return null;
        }
        return $this->received_qty - $this->expected_qty;
    }
}
