<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ShippingOrderPublicLink extends Model
{
    protected $fillable = [
        'shipping_order_id',
        'token',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // ========== Relationships ==========

    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    // ========== Scopes ==========

    /**
     * Scope to filter active and non-expired links.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to find by token.
     */
    public function scopeByToken(Builder $query, string $token): Builder
    {
        return $query->where('token', $token);
    }

    // ========== Helpers ==========

    /**
     * Check if the link is valid (active and not expired).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Generate the public tracking URL.
     */
    public function getPublicUrlAttribute(): string
    {
        return url("/track/{$this->token}");
    }

    /**
     * Generate a secure random token.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Create a new public link for a shipping order.
     */
    public static function createForOrder(ShippingOrder $shippingOrder, ?\DateTimeInterface $expiresAt = null): self
    {
        return static::create([
            'shipping_order_id' => $shippingOrder->id,
            'token' => static::generateToken(),
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);
    }
}
