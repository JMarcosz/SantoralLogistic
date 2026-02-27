<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'tax_id',
        'tax_id_type', // Nuevos campos
        'fiscal_name', // Nuevos campos
        'ncf_type_default', // Fixed typo: was 'nfc_type_default'
        'series', // Added missing series field
        'billing_address',
        'shipping_address',
        'city',
        'state',
        'country',
        'email_billing',
        'phone',
        'website',
        'status',
        'credit_limit',
        'currency_id',
        'payment_terms',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ========== Relationships ==========

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function activeContacts(): HasMany
    {
        return $this->hasMany(Contact::class)->where('is_active', true);
    }

    public function primaryContact(): HasMany
    {
        return $this->hasMany(Contact::class)->where('is_primary', true);
    }

    /**
     * Get all inventory items belonging to this customer.
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    // ========== Scopes ==========

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeProspects(Builder $query): Builder
    {
        return $query->where('status', 'prospect');
    }

    public function scopeActiveCustomers(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeFromCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
                ->orWhere('code', 'ilike', "%{$search}%")
                ->orWhere('tax_id', 'ilike', "%{$search}%")
                ->orWhere('email_billing', 'ilike', "%{$search}%");
        });
    }

    // ========== Accessors ==========

    public function getDisplayNameAttribute(): string
    {
        return $this->code ? "{$this->code} - {$this->name}" : $this->name;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->billing_address,
            $this->city,
            $this->state,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'prospect' => 'Prospecto',
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            default => $this->status,
        };
    }

    // ========== Business Logic ==========

    public function getAddressAttribute(): ?string
    {
        return $this->billing_address ?? $this->shipping_address;
    }

    public function hasAvailableCredit(float $amount): bool
    {
        if (!$this->credit_limit) {
            return true; // No limit set
        }

        // TODO: Calculate used credit from invoices
        return $amount <= $this->credit_limit;
    }
}
