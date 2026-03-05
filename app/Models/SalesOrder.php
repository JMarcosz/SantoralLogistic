<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class SalesOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'quote_id',
        'customer_id',
        'contact_id',
        'currency_id',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'confirmed_at',
        'delivered_at',
        'created_by',
    ];

    protected $casts = [
        'status' => SalesOrderStatus::class,
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'confirmed_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'subtotal' => 0,
        'tax_amount' => 0,
        'total_amount' => 0,
    ];

    // ========== Boot ==========

    protected static function booted(): void
    {
        static::creating(function (SalesOrder $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
            if (empty($order->created_by)) {
                $order->created_by = Auth::id();
            }
        });
    }

    /**
     * Generate the next order number for the current year.
     * Format: PED-YYYY-NNNNNN
     */
    public static function generateOrderNumber(): string
    {
        $year = now()->year;
        $prefix = "PED-{$year}-";

        $lastOrder = static::withTrashed()
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTRING(order_number, 10) AS UNSIGNED) DESC")
            ->first();

        if ($lastOrder) {
            $lastSequence = (int) substr($lastOrder->order_number, 9);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
    }

    // ========== Relationships ==========

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Sales order lines (products and services).
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->orderBy('sort_order');
    }

    /**
     * Only product lines (inventoriable items).
     */
    public function productLines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->where('line_type', 'product');
    }

    /**
     * Only service lines.
     */
    public function serviceLines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class)->where('line_type', 'service');
    }

    /**
     * Inventory reservations for this sales order.
     */
    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    /**
     * Invoices generated from this sales order.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('created_at', 'desc');
    }

    // ========== Scopes ==========

    public function scopeOfStatus(Builder $query, SalesOrderStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof SalesOrderStatus ? $status->value : $status);
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    // ========== Business Logic ==========

    /**
     * Check if the order is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Check if a status transition is valid.
     */
    public function canTransitionTo(SalesOrderStatus $targetStatus): bool
    {
        return $this->status->canTransitionTo($targetStatus);
    }

    /**
     * Check if this order can be confirmed (reserves inventory).
     */
    public function canConfirm(): bool
    {
        return $this->status === SalesOrderStatus::Draft;
    }

    /**
     * Check if this order can start delivery.
     */
    public function canDeliver(): bool
    {
        return $this->status === SalesOrderStatus::Confirmed;
    }

    /**
     * Check if this order can be invoiced.
     */
    public function canInvoice(): bool
    {
        return $this->status === SalesOrderStatus::Delivered;
    }

    /**
     * Check if the order has reserved inventory.
     */
    public function hasReservedInventory(): bool
    {
        return $this->inventoryReservations()->exists();
    }

    /**
     * Check if this order has been invoiced.
     */
    public function hasBeenInvoiced(): bool
    {
        return $this->invoices()->exists();
    }

    /**
     * Recalculate totals from lines.
     */
    public function recalculateTotals(): void
    {
        $this->load('lines');

        $subtotal = 0;
        $taxAmount = 0;

        foreach ($this->lines as $line) {
            $lineSubtotal = $line->quantity * $line->unit_price * (1 - $line->discount_percent / 100);
            $lineTax = $lineSubtotal * ($line->tax_rate / 100);
            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
        }

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ]);
    }
}
