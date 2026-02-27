<?php

namespace App\Models;

use App\Enums\MilestoneCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingOrderMilestone extends Model
{
    protected $fillable = [
        'shipping_order_id',
        'code',
        'label',
        'status',
        'happened_at',
        'location',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    // ========== Relationships ==========

    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========== Scopes ==========

    public function scopeOfCode($query, string|MilestoneCode $code)
    {
        return $query->where('code', $code instanceof MilestoneCode ? $code->value : $code);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('happened_at', 'desc');
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('happened_at', 'asc');
    }

    // ========== Accessors ==========

    /**
     * Try to get the enum representation if it's a standard code.
     */
    public function getMilestoneCodeEnumAttribute(): ?MilestoneCode
    {
        return MilestoneCode::tryFrom($this->code);
    }

    /**
     * Get icon from enum or default.
     */
    public function getIconAttribute(): string
    {
        return $this->milestoneCodeEnum?->icon() ?? 'circle';
    }

    /**
     * Get color from enum or default.
     */
    public function getColorAttribute(): string
    {
        return $this->milestoneCodeEnum?->color() ?? 'gray';
    }
}
