<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Port extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'country',
        'city',
        'unlocode',
        'iata_code',
        'type',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active ports.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include air ports (airports).
     */
    public function scopeAir(Builder $query): Builder
    {
        return $query->where('type', 'air');
    }

    /**
     * Scope a query to only include ocean ports (seaports).
     */
    public function scopeOcean(Builder $query): Builder
    {
        return $query->where('type', 'ocean');
    }

    /**
     * Scope a query to only include ground locations.
     */
    public function scopeGround(Builder $query): Builder
    {
        return $query->where('type', 'ground');
    }

    /**
     * Get the display label for the port (code + name).
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
