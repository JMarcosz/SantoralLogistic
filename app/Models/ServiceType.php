<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'scope',
        'default_incoterm',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Scope a query to only include active service types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by scope.
     */
    public function scopeOfScope(Builder $query, string $scope): Builder
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope a query to only include the default service type.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the display label for the service type (code + name).
     */
    public function getDisplayLabelAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Ensure only one service type can be default
        static::saving(function (ServiceType $serviceType) {
            if ($serviceType->is_default) {
                static::where('id', '!=', $serviceType->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
