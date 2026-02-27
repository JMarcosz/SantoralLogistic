<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    // Term types
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_QUOTE_FOOTER = 'quote_footer';
    public const TYPE_SO_FOOTER = 'shipping_order_footer';
    public const TYPE_INVOICE_FOOTER = 'invoice_footer';

    // All valid types
    public const TYPES = [
        self::TYPE_PAYMENT => 'Términos de Pago',
        self::TYPE_QUOTE_FOOTER => 'Pie de Cotización',
        self::TYPE_SO_FOOTER => 'Pie de Orden de Envío',
        self::TYPE_INVOICE_FOOTER => 'Pie de Factura',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'body',
        'type',
        'scope',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ========== Scopes ==========

    /**
     * Scope to only active terms.
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
     * Scope to get default terms.
     */
    public function scopeDefaults(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    // ========== Accessors ==========

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    // ========== Static Helpers ==========

    /**
     * Get the default term for a given type.
     */
    public static function getDefault(string $type): ?self
    {
        return static::active()
            ->ofType($type)
            ->defaults()
            ->first();
    }

    /**
     * Get all active terms of a type for dropdown.
     */
    public static function getOptionsForType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::select('id', 'code', 'name')
            ->active()
            ->ofType($type)
            ->orderBy('name')
            ->get();
    }
}
