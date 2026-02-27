<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLine extends Model
{
    protected $fillable = [
        'quote_id',
        'product_service_id',
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
        'sort_order' => 'integer',
    ];

    protected $hidden = [
        'unit_cost',
        'total_cost',
        'profit',
    ];

    // ========== Relationships ==========

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function productService(): BelongsTo
    {
        return $this->belongsTo(ProductService::class);
    }

    // ========== Accessors ==========

    /**
     * Get the subtotal before discount.
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }

    /**
     * Get the discount amount.
     */
    public function getDiscountAmountAttribute(): float
    {
        return $this->subtotal * ((float) $this->discount_percent / 100);
    }

    /**
     * Get the net amount (subtotal - discount).
     */
    public function getNetAmountAttribute(): float
    {
        return $this->subtotal - $this->discount_amount;
    }

    /**
     * Get the tax amount.
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->net_amount * ((float) $this->tax_rate / 100);
    }

    /**
     * Get the total including tax.
     */
    public function getTotalWithTaxAttribute(): float
    {
        return $this->net_amount + $this->tax_amount;
    }

    /**
     * Get the total cost for this line.
     */
    public function getTotalCostAttribute(): ?float
    {
        if ($this->unit_cost === null) {
            return null;
        }
        return (float) $this->quantity * (float) $this->unit_cost;
    }

    /**
     * Get the profit for this line.
     */
    public function getProfitAttribute(): ?float
    {
        $totalCost = $this->total_cost;
        if ($totalCost === null) {
            return null;
        }
        return $this->subtotal - $totalCost;
    }

    // ========== Boot ==========

    protected static function booted(): void
    {
        // Calculate line_total on saving
        static::saving(function (QuoteLine $line) {
            $subtotal = (float) $line->quantity * (float) $line->unit_price;
            $discount = $subtotal * ((float) $line->discount_percent / 100);
            $line->line_total = $subtotal - $discount;
        });

        // Recalculate quote totals on saved/deleted
        static::saved(function (QuoteLine $line) {
            $line->quote?->recalculateTotal();
        });

        static::deleted(function (QuoteLine $line) {
            $line->quote?->recalculateTotal();
        });
    }
}
