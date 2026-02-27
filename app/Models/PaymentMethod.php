<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PaymentMethod Model
 * 
 * Represents a payment method (Cash, Check, Wire Transfer, etc.)
 */
class PaymentMethod extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ========== Relationships ==========

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ========== Static Helpers ==========

    public static function forDropdown(): array
    {
        return static::active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }
}
