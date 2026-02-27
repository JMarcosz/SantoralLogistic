<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PreInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'customer_id',
        'shipping_order_id',
        'currency_code',
        'issue_date',
        'due_date',
        'status', // draft, issued, paid, cancelled
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'paid_at',
        'notes',
        'external_ref',
        'exported_at',
        'export_reference',
        'invoiced_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'balance' => 'decimal:4',
        'paid_at' => 'datetime',
        'exported_at' => 'datetime',
        'invoiced_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PreInvoiceLine::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('payment_date', 'desc');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Scopes for Accounts Receivable
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_ISSUED)
            ->where('balance', '>', 0);
    }

    public function scopeOverdue($query)
    {
        return $query->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay());
    }

    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeByCurrency($query, string $currencyCode)
    {
        return $query->where('currency_code', $currencyCode);
    }

    /**
     * Helpers
     */
    public function isExported(): bool
    {
        return $this->exported_at !== null;
    }

    public function markAsExported(?string $reference = null): void
    {
        $this->update([
            'exported_at' => now(),
            'export_reference' => $reference,
        ]);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->balance <= 0;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_ISSUED && $this->balance > 0;
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() &&
            $this->due_date !== null &&
            $this->due_date->lt(now()->startOfDay());
    }

    /**
     * Get days overdue (negative if not yet due)
     */
    public function getDaysOverdueAttribute(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        return (int) $this->due_date->startOfDay()->diffInDays(now()->startOfDay(), false);
    }

    /**
     * Get aging bucket
     */
    public function getAgingBucketAttribute(): string
    {
        $days = $this->days_overdue;

        if ($days <= 0) {
            return 'current';
        } elseif ($days <= 30) {
            return '1_30';
        } elseif ($days <= 60) {
            return '31_60';
        } elseif ($days <= 90) {
            return '61_90';
        } else {
            return 'over_90';
        }
    }

    /**
     * Recalculate balance from approved payments
     */
    public function recalculateBalance(): void
    {
        $approvedPayments = $this->payments()
            ->where('status', Payment::STATUS_APPROVED)
            ->sum('amount');

        $this->paid_amount = $approvedPayments;
        $this->balance = max(0, $this->total_amount - $approvedPayments);

        // Update status if fully paid
        if ($this->balance <= 0 && $this->status === self::STATUS_ISSUED) {
            $this->status = self::STATUS_PAID;
            $this->paid_at = now();
        } elseif ($this->balance > 0 && $this->status === self::STATUS_PAID) {
            // Revert to issued if payment voided
            $this->status = self::STATUS_ISSUED;
            $this->paid_at = null;
        }

        $this->save();
    }

    /**
     * Check if this pre-invoice has been converted to a fiscal invoice.
     */
    public function hasBeenInvoiced(): bool
    {
        return $this->invoiced_at !== null;
    }

    /**
     * Check if this pre-invoice can be invoiced (is in issued status and not yet invoiced).
     */
    public function canBeInvoiced(): bool
    {
        return $this->status === self::STATUS_ISSUED && !$this->hasBeenInvoiced();
    }
}
