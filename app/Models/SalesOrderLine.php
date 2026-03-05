<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'product_service_id',
        'line_type',
        'description',
        'quantity',
        'unit_price',
        'unit_cost',
        'discount_percent',
        'tax_rate',
        'line_total',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount_percent' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:4',
    ];

    // ========== Relationships ==========

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function productService(): BelongsTo
    {
        return $this->belongsTo(ProductService::class);
    }

    // ========== Accessors ==========

    /**
     * Check if this line is a product (inventoriable).
     */
    public function isProduct(): bool
    {
        return $this->line_type === 'product';
    }

    /**
     * Check if this line is a service.
     */
    public function isService(): bool
    {
        return $this->line_type === 'service';
    }

    /**
     * Calculate subtotal (before tax).
     */
    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price * (1 - $this->discount_percent / 100);
    }

    /**
     * Calculate tax amount for this line.
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->subtotal * ($this->tax_rate / 100);
    }

    /**
     * Calculate net total (subtotal + tax).
     */
    public function getNetTotalAttribute(): float
    {
        return $this->subtotal + $this->tax_amount;
    }
}
