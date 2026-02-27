<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class PackageType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'length_cm',
        'width_cm',
        'height_cm',
        'max_weight_kg',
        'is_container',
        'is_active',
    ];

    protected $casts = [
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'is_container' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active package types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include containers.
     */
    public function scopeContainers(Builder $query): Builder
    {
        return $query->where('is_container', true);
    }

    /**
     * Get the volume in cubic centimeters.
     */
    public function getVolumeCm3Attribute(): ?float
    {
        if ($this->length_cm && $this->width_cm && $this->height_cm) {
            return $this->length_cm * $this->width_cm * $this->height_cm;
        }
        return null;
    }

    /**
     * Get the volume in cubic meters.
     */
    public function getVolumeM3Attribute(): ?float
    {
        $volumeCm3 = $this->volume_cm3;
        if ($volumeCm3) {
            return $volumeCm3 / 1000000; // 100^3
        }
        return null;
    }

    /**
     * Get formatted dimensions string.
     */
    public function getDimensionsAttribute(): ?string
    {
        if ($this->length_cm && $this->width_cm && $this->height_cm) {
            return "{$this->length_cm} x {$this->width_cm} x {$this->height_cm} cm";
        }
        return null;
    }
}
