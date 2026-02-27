<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'ncf',
        'ncf_type',
        'customer_id',
        'pre_invoice_id',
        'shipping_order_id',
        'currency_code',
        'issue_date',
        'due_date',
        'status',
        'payment_status',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance',
        'exempt_amount',
        'taxable_amount',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'cancelled_at' => 'datetime',
        'subtotal_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'amount_paid' => 'decimal:4',
        'balance' => 'decimal:4',
        'exempt_amount' => 'decimal:4',
        'taxable_amount' => 'decimal:4',
    ];

    // Status constants
    public const STATUS_ISSUED = 'issued';
    public const STATUS_CANCELLED = 'cancelled';

    // Payment status constants
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PARTIAL = 'partial';
    public const PAYMENT_STATUS_PAID = 'paid';

    /**
     * Boot method - Auto-generate invoice number.
     */
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->number)) {
                $invoice->number = static::generateInvoiceNumber();
            }
            // Initialize balance to total_amount if not set
            if (is_null($invoice->balance)) {
                $invoice->balance = $invoice->total_amount ?? 0;
            }
            if (is_null($invoice->payment_status)) {
                $invoice->payment_status = self::PAYMENT_STATUS_PENDING;
            }
        });
    }

    /**
     * Generate the next invoice number for the current year.
     * Format: INV-YYYY-NNNNNN
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";

        // Find last invoice number with lock to prevent race conditions
        $last = static::where('number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderBy('number', 'desc')
            ->first();

        if (!$last) {
            return $prefix . '000001';
        }

        // Extract sequence
        $sequence = (int) Str::after($last->number, $prefix);
        $next = $sequence + 1;

        return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function preInvoice(): BelongsTo
    {
        return $this->belongsTo(PreInvoice::class);
    }

    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort_order');
    }

    /**
     * Get all payment allocations for this invoice.
     */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the journal entry for this invoice.
     */
    public function journalEntry(): ?BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'source_id', 'id')
            ->where('source_type', 'invoice');
    }

    /**
     * Get the journal entry created for this invoice via morphMany.
     */
    public function journalEntries()
    {
        return JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $this->id);
    }

    /**
     * Scopes
     */
    public function scopeIssued($query)
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByNcfType($query, string $ncfType)
    {
        return $query->where('ncf_type', $ncfType);
    }

    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('issue_date', [$from, $to]);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Cancel this invoice.
     * 
     * @param string $reason Motivo de la cancelación
     * @throws \InvalidArgumentException If invoice is already cancelled
     */
    public function cancel(string $reason): void
    {
        if ($this->isCancelled()) {
            throw new \InvalidArgumentException('Invoice is already cancelled');
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Check if this invoice is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if this invoice can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    /**
     * Check if this invoice can receive payments.
     */
    public function canReceivePayment(): bool
    {
        return $this->status === self::STATUS_ISSUED
            && $this->payment_status !== self::PAYMENT_STATUS_PAID
            && $this->balance > 0;
    }

    /**
     * Recalculate payment totals from allocations.
     * Called when payment allocations change.
     */
    public function recalculatePayments(): void
    {
        // Sum all allocations from non-voided payments
        // Draft, pending, approved, and posted payments all count toward the balance
        $amountPaid = $this->paymentAllocations()
            ->whereHas('payment', function ($q) {
                $q->where('status', '!=', 'voided');
            })
            ->sum('amount_applied');

        $balance = $this->total_amount - $amountPaid;

        // Determine payment status
        $paymentStatus = self::PAYMENT_STATUS_PENDING;
        if ($balance <= 0 && $amountPaid > 0) {
            $paymentStatus = self::PAYMENT_STATUS_PAID;
            $balance = 0; // Prevent negative balance
        } elseif ($amountPaid > 0) {
            $paymentStatus = self::PAYMENT_STATUS_PARTIAL;
        }

        $this->update([
            'amount_paid' => $amountPaid,
            'balance' => $balance,
            'payment_status' => $paymentStatus,
        ]);
    }

    /**
     * Check if this invoice is fully paid.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    /**
     * Check if this invoice is partially paid.
     */
    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PARTIAL;
    }

    /**
     * Get the origin source of this invoice (PreInvoice or ShippingOrder).
     * 
     * @return PreInvoice|ShippingOrder|null
     */
    public function getOriginSource()
    {
        if ($this->pre_invoice_id) {
            return $this->preInvoice;
        }

        if ($this->shipping_order_id) {
            return $this->shippingOrder;
        }

        return null;
    }

    /**
     * Get the origin type as a string.
     */
    public function getOriginTypeAttribute(): ?string
    {
        if ($this->pre_invoice_id) {
            return 'pre_invoice';
        }

        if ($this->shipping_order_id) {
            return 'shipping_order';
        }

        return null;
    }
}
