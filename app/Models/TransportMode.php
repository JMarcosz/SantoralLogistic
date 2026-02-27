<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransportMode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'supports_awb',
        'supports_bl',
        'supports_pod',
        'is_active',
    ];

    protected $casts = [
        'supports_awb' => 'boolean',
        'supports_bl' => 'boolean',
        'supports_pod' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to only include active transport modes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by AWB support.
     */
    public function scopeWithAwb(Builder $query): Builder
    {
        return $query->where('supports_awb', true);
    }

    /**
     * Scope to filter by B/L support.
     */
    public function scopeWithBl(Builder $query): Builder
    {
        return $query->where('supports_bl', true);
    }

    /**
     * Get display label (code - name).
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
