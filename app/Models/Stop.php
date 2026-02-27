<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stop extends Model
{
    use HasFactory;

    protected $fillable = [
        'stoppable_type',
        'stoppable_id',
        'sequence',
        'name',
        'address',
        'city',
        'country',
        'window_start',
        'window_end',
        'contact_name',
        'contact_phone',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'window_start' => 'datetime',
        'window_end' => 'datetime',
    ];

    /**
     * Get the parent stoppable model (PickupOrder or DeliveryOrder).
     */
    public function stoppable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for ordering by sequence.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    /**
     * Get the full address string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->country,
        ]);

        return implode(', ', $parts);
    }
}
