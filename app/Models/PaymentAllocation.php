<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentAllocation Model
 * 
 * Represents the allocation of a payment to an invoice.
 * Allows partial payments and multi-invoice payments.
 */
class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount_applied',
        'exchange_rate',
        'base_amount_applied',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'base_amount_applied' => 'decimal:4',
    ];

    // ========== Relationships ==========

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ========== Boot ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($allocation) {
            if (empty($allocation->base_amount_applied)) {
                $allocation->base_amount_applied = $allocation->amount_applied * ($allocation->exchange_rate ?? 1);
            }
        });

        // Update payment totals when allocations change
        static::saved(function ($allocation) {
            $allocation->payment->recalculateAllocations();
            // Also recalculate invoice balance
            $allocation->invoice->recalculatePayments();
        });

        static::deleted(function ($allocation) {
            $allocation->payment->recalculateAllocations();
            // Also recalculate invoice balance
            $allocation->invoice->recalculatePayments();
        });
    }
}
