<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'phone',
        'position',
        'contact_type',
        'is_primary',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ========== Relationships ==========

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========== Scopes ==========

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('contact_type', $type);
    }

    // ========== Accessors ==========

    public function getContactTypeLabelAttribute(): string
    {
        return match ($this->contact_type) {
            'general' => 'General',
            'billing' => 'Facturación',
            'operations' => 'Operaciones',
            'sales' => 'Ventas',
            default => $this->contact_type ?? 'General',
        };
    }

    // ========== Business Logic ==========

    /**
     * Set this contact as the primary contact for the customer.
     * Automatically unsets other primary contacts.
     */
    public function setAsPrimary(): void
    {
        // Unset other primary contacts for this customer
        static::where('customer_id', $this->customer_id)
            ->where('id', '!=', $this->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        // Set this one as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // When saving, if is_primary is true, unset others
        static::saving(function (Contact $contact) {
            if ($contact->is_primary && $contact->isDirty('is_primary')) {
                static::where('customer_id', $contact->customer_id)
                    ->where('id', '!=', $contact->id ?? 0)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
