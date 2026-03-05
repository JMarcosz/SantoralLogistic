<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductService extends Model
{
    use SoftDeletes;

    protected $table = 'products_services';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'uom',
        'default_currency_id',
        'default_unit_price',
        'taxable',
        'gl_account_code',
        'is_active',
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:4',
        'taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default currency.
     */
    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    /**
     * Scope to only include active products/services.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter only services.
     */
    public function scopeServices(Builder $query): Builder
    {
        return $query->where('type', 'service');
    }

    /**
     * Scope to filter only products.
     */
    public function scopeProducts(Builder $query): Builder
    {
        return $query->where('type', 'product');
    }

    /**
     * Scope to filter only fees.
     */
    public function scopeFees(Builder $query): Builder
    {
        return $query->where('type', 'fee');
    }

    /**
     * Scope to filter taxable items.
     */
    public function scopeTaxable(Builder $query): Builder
    {
        return $query->where('taxable', true);
    }

    /**
     * Get display label.
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Inventory items linked to this product.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'product_service_id');
    }

    /**
     * Check if this is a product (inventoriable).
     */
    public function isProduct(): bool
    {
        return $this->type === 'product';
    }

    /**
     * Check if this is a service.
     */
    public function isService(): bool
    {
        return $this->type === 'service';
    }

    /**
     * Check if this is a fee.
     */
    public function isFee(): bool
    {
        return $this->type === 'fee';
    }
}
