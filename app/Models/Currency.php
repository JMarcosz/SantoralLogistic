<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Scope to only include active currencies (non-deleted).
     */
    public function scopeActive($query)
    {
        return $query;
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Ensure only one currency can be default
        static::saving(function (Currency $currency) {
            if ($currency->is_default) {
                // Unset other default currencies
                static::where('id', '!=', $currency->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}
