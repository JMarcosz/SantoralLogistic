<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payment Model
 * 
 * Represents a payment (inbound from customer or outbound to supplier).
 * Supports multi-invoice allocation and multi-currency with exchange rate tracking.
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'type',
        'pre_invoice_id',
        'customer_id',
        'supplier_id',
        'payment_method_id',
        'amount',
        'currency_code',
        'exchange_rate',
        'base_amount',
        'isr_withholding_amount',
        'itbis_withholding_amount',
        'net_amount',
        'amount_allocated',
        'amount_unapplied',
        'payment_date',
        'reference',
        'notes',
        'status',
        'bank_account_id',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'type' => PaymentType::class,
        'amount' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'base_amount' => 'decimal:4',
        'isr_withholding_amount' => 'decimal:4',
        'itbis_withholding_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'amount_allocated' => 'decimal:4',
        'amount_unapplied' => 'decimal:4',
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_VOIDED = 'voided';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';

    // ========== Boot ==========

    // Legacy payment method options
    public const METHODS = [
        'cash' => 'Efectivo',
        'check' => 'Cheque',
        'transfer' => 'Transferencia',
        'card' => 'Tarjeta',
        'other' => 'Otro',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = static::generatePaymentNumber($payment->type ?? PaymentType::Inbound);
            }
            if (is_null($payment->amount_unapplied)) {
                $payment->amount_unapplied = $payment->amount;
            }
            if (is_null($payment->base_amount)) {
                $payment->base_amount = $payment->amount * ($payment->exchange_rate ?? 1);
            }
            if (empty($payment->type)) {
                $payment->type = PaymentType::Inbound;
            }
        });
    }

    // ========== Relationships ==========

    public function preInvoice(): BelongsTo
    {
        return $this->belongsTo(PreInvoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Supplier relationship - uncomment when Supplier model is created
    // public function supplier(): BelongsTo
    // {
    //     return $this->belongsTo(Supplier::class);
    // }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    // ========== Scopes ==========

    public function scopeInbound($query)
    {
        return $query->where('type', PaymentType::Inbound);
    }

    public function scopeOutbound($query)
    {
        return $query->where('type', PaymentType::Outbound);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_DRAFT, self::STATUS_POSTED]);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    // ========== Accessors ==========

    /**
     * Get the payer/payee name.
     */
    public function getPayerNameAttribute(): ?string
    {
        if ($this->type === PaymentType::Inbound) {
            return $this->customer?->name ?? $this->preInvoice?->customer?->name;
        }
        return $this->supplier?->name;
    }

    /**
     * Check if payment has unallocated balance.
     */
    public function getHasUnallocatedBalanceAttribute(): bool
    {
        return ($this->amount_unapplied ?? 0) > 0;
    }

    public function getMethodLabelAttribute(): string
    {
        if ($this->paymentMethod) {
            return $this->paymentMethod->name;
        }
        return 'N/A';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_APPROVED, self::STATUS_POSTED => 'Contabilizado',
            self::STATUS_VOIDED => 'Anulado',
            self::STATUS_DRAFT => 'Borrador',
            default => $this->status ?? 'N/A',
        };
    }

    // ========== Business Logic ==========

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED || $this->status === self::STATUS_POSTED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function canPost(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]) && $this->amount > 0;
    }

    public function canVoid(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_POSTED]);
    }

    public function canDelete(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * Recalculate allocation totals.
     */
    public function recalculateAllocations(): void
    {
        $allocated = $this->allocations()->sum('amount_applied');
        $this->update([
            'amount_allocated' => $allocated,
            'amount_unapplied' => $this->amount - $allocated,
        ]);
    }

    // ========== Static Helpers ==========

    /**
     * Generate a unique payment number.
     */
    public static function generatePaymentNumber(PaymentType|string $type): string
    {
        $typeValue = $type instanceof PaymentType ? $type->value : $type;
        $prefix = $typeValue === 'inbound' ? 'REC' : 'PAY'; // RECeipt vs PAYment
        $year = now()->format('Y');

        $lastNumber = static::where('payment_number', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('payment_number');

        if ($lastNumber) {
            $sequence = (int) substr($lastNumber, -6) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}
