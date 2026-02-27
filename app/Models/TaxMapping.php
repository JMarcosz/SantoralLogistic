<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TaxMapping Model
 * 
 * Maps tax types (ITBIS, ISC, etc.) to GL accounts for automatic posting.
 */
class TaxMapping extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'rate',
        'sales_account_id',
        'purchase_account_id',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // ========== Relationships ==========

    /**
     * Tax payable account (for sales).
     */
    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    /**
     * Tax receivable account (for purchases).
     */
    public function purchaseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ========== Static Methods ==========

    /**
     * Get the default tax mapping (usually ITBIS).
     */
    public static function getDefault(): ?self
    {
        return static::active()->default()->first()
            ?? static::active()->first();
    }

    /**
     * Find by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    // ========== Boot ==========

    protected static function booted(): void
    {
        // Ensure only one tax can be default
        static::saving(function (TaxMapping $tax) {
            if ($tax->is_default) {
                static::where('id', '!=', $tax->id ?? 0)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
