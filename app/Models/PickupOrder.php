<?php

namespace App\Models;

use App\Enums\PickupOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PickupOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_order_id',
        'customer_id',
        'driver_id',
        'reference',
        'status',
        'scheduled_date',
        'notes',
    ];

    protected $casts = [
        'status' => PickupOrderStatus::class,
        'scheduled_date' => 'date',
    ];

    /**
     * Get the shipping order this pickup belongs to.
     */
    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    /**
     * Get the customer for this pickup order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the assigned driver.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get all stops for this pickup order.
     */
    public function stops(): MorphMany
    {
        return $this->morphMany(Stop::class, 'stoppable')->orderBy('sequence');
    }

    /**
     * Get the POD (Proof of Delivery) for this pickup order.
     */
    public function pod(): MorphOne
    {
        return $this->morphOne(Pod::class, 'podable');
    }

    /**
     * Scope for orders by status.
     */
    public function scopeStatus($query, PickupOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', PickupOrderStatus::Pending);
    }

    /**
     * Scope for orders scheduled on a specific date.
     */
    public function scopeScheduledOn($query, $date)
    {
        return $query->whereDate('scheduled_date', $date);
    }
}
