<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Traits\GeneratesQuoteNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Charge;
use App\Models\CompanySetting;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quote extends Model
{
    use HasFactory, SoftDeletes, GeneratesQuoteNumber;

    protected $fillable = [
        'quote_number',
        'customer_id',
        'contact_id',
        'origin_port_id',
        'destination_port_id',
        'transport_mode_id',
        'service_type_id',
        'currency_id',
        'status',
        'total_pieces',
        'total_weight_kg',
        'total_volume_cbm',
        'chargeable_weight_kg',
        'subtotal',
        'tax_amount',
        'total_amount',
        'valid_until',
        'notes',
        'terms', // Deprecated - use payment_terms_snapshot and footer_terms_snapshot
        'payment_terms_id',
        'payment_terms_snapshot',
        'footer_terms_id',
        'footer_terms_snapshot',
        'footer_terms_snapshot',
        'created_by',
        'sales_rep_id',
        // Expanded Fields
        'division_id',
        'transit_days',
        'incoterms',
        'project_id',
        'issuing_company_id',
        'carrier_id',
        'shipper_id',
        'consignee_id',
        'pickup_address',
        'delivery_address',
    ];

    protected $casts = [
        'status' => QuoteStatus::class,
        'valid_until' => 'date',
        'total_pieces' => 'integer',
        'total_weight_kg' => 'decimal:3',
        'total_volume_cbm' => 'decimal:3',
        'chargeable_weight_kg' => 'decimal:3',
        'subtotal' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'payment_terms_snapshot' => 'array',
        'footer_terms_snapshot' => 'array',
        'transit_days' => 'integer',
    ];

    // ========== Relationships ==========

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function issuingCompany(): BelongsTo
    {
        return $this->belongsTo(CompanySetting::class, 'issuing_company_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'shipper_id');
    }

    public function consignee(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consignee_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ... existing relations ...


    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function originPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'origin_port_id');
    }

    public function destinationPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'destination_port_id');
    }

    public function transportMode(): BelongsTo
    {
        return $this->belongsTo(TransportMode::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * Get the shipping order created from this quote.
     */
    public function shippingOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ShippingOrder::class);
    }

    /**
     * Check if this quote has been converted to a shipping order.
     */
    public function hasShippingOrder(): bool
    {
        return $this->shippingOrder()->exists();
    }

    /**
     * Get the sales order created from this quote.
     */
    public function salesOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }

    /**
     * Check if this quote has been converted to a sales order.
     */
    public function hasSalesOrder(): bool
    {
        return $this->salesOrder()->exists();
    }

    /**
     * Payment terms relationship.
     */
    public function paymentTerms(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'payment_terms_id');
    }

    /**
     * Footer terms relationship.
     */
    public function footerTerms(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'footer_terms_id');
    }

    /**
     * Charges associated with this quote.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class)->orderBy('sort_order');
    }

    // ========== Scopes ==========

    public function scopeOfStatus(Builder $query, QuoteStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', QuoteStatus::Draft->value);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', QuoteStatus::Sent->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', QuoteStatus::Approved->value);
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now()->toDateString());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('valid_until')
            ->where('valid_until', '<', now()->toDateString());
    }

    public function scopeForLane(Builder $query, int $originId, int $destinationId): Builder
    {
        return $query->where('origin_port_id', $originId)
            ->where('destination_port_id', $destinationId);
    }

    protected $hidden = [
        'total_cost',
        'total_profit',
        'profit_margin',
    ];

    // ========== Accessors ==========

    public function getTotalCostAttribute(): ?float
    {
        // If any line has null cost, return null to indicate incomplete data
        if ($this->lines->contains(fn($line) => $line->unit_cost === null)) {
            return null;
        }
        return $this->lines->sum('total_cost');
    }

    public function getTotalProfitAttribute(): ?float
    {
        $cost = $this->total_cost;
        if ($cost === null) {
            return null;
        }
        return $this->subtotal - $cost;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->subtotal <= 0) {
            return 0;
        }
        return ($this->total_profit / $this->subtotal) * 100;
    }

    public function getLaneAttribute(): string
    {
        return "{$this->originPort?->code} → {$this->destinationPort?->code}";
    }

    public function scopeAccessibleBy(Builder $query, \App\Models\User $user): Builder
    {
        if ($user->hasRole(['admin', 'super-admin', 'owner'])) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
                ->orWhere('sales_rep_id', $user->id);
        });
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->valid_until) {
            return false;
        }

        return $this->valid_until->lt(now()->startOfDay());
    }

    public function getFormattedTotalAttribute(): string
    {
        $symbol = $this->currency?->symbol ?? '$';
        return $symbol . number_format($this->total_amount, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    // ========== Business Logic ==========

    /**
     * Recalculate totals from lines.
     */
    /**
     * Recalculate totals from lines AND items.
     */
    public function recalculateTotal(): void
    {
        // 1. Calculate Financials (Prices) from QuoteLines
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($this->lines as $line) {
            $lineSubtotal = $line->quantity * $line->unit_price;
            $discount = $lineSubtotal * ($line->discount_percent / 100);
            $lineNet = $lineSubtotal - $discount;
            $lineTax = $lineNet * ($line->tax_rate / 100);

            $subtotal += $lineNet;
            $taxAmount += $lineTax;
        }

        // 2. Calculate Physical Totals from QuoteItems
        $totalWeight = 0;
        $totalVolume = 0;
        $totalPieces = 0;

        $this->loadMissing(['items.lines']); // Ensure items are loaded

        foreach ($this->items as $item) {
            // Sum from Item Lines
            foreach ($item->lines as $itemLine) {
                $totalWeight += $itemLine->weight_kg;
                $totalVolume += $itemLine->volume_cbm;
                $totalPieces += $itemLine->pieces;
            }

            // Add Tare Weight if exists in properties
            $properties = $item->properties ?? [];
            if (isset($properties['tare_weight'])) {
                $totalWeight += (float) $properties['tare_weight'];
            }
        }

        // If no items, fallback to manual entry (or keep existing if we want hybrid?)
        // For now, if items exist, they overwrite the manual totals.
        $physicalUpdates = [];
        if ($this->items->count() > 0) {
            $physicalUpdates = [
                'total_weight_kg' => $totalWeight,
                'total_volume_cbm' => $totalVolume,
                'total_pieces' => $totalPieces,
                'chargeable_weight_kg' => max($totalWeight, $totalVolume * 166.67), // Standard conversion
            ];
        }

        $this->update(array_merge([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ], $physicalUpdates));
    }

    /**
     * Add a line to the quote.
     */
    public function addLine(
        ProductService $product,
        float $quantity = 1,
        ?float $unitPrice = null,
        ?string $description = null,
        ?float $unitCost = null
    ): QuoteLine {
        $line = $this->lines()->create([
            'product_service_id' => $product->id,
            'description' => $description ?? $product->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice ?? $product->default_unit_price ?? 0,
            'unit_cost' => $unitCost,
            'tax_rate' => $product->taxable ? 18.00 : 0, // Default ITBIS
            'discount_percent' => 0,
            'line_total' => 0, // Will be calculated by QuoteLine
            'sort_order' => $this->lines()->count(),
        ]);

        $this->recalculateTotal();

        return $line;
    }

    /**
     * Remove a line from the quote.
     */
    public function removeLine(QuoteLine $line): void
    {
        $line->delete();
        $this->recalculateTotal();
    }

    /**
     * Mark quote as sent.
     *
     * @throws \App\Exceptions\InvalidQuoteStateTransitionException
     */
    public function markAsSent(): void
    {
        if (!$this->status->canSend()) {
            throw \App\Exceptions\InvalidQuoteStateTransitionException::cannotSend($this->status->value);
        }

        $this->update(['status' => QuoteStatus::Sent]);
    }

    /**
     * Approve the quote.
     *
     * @throws \App\Exceptions\InvalidQuoteStateTransitionException
     */
    public function approve(): void
    {
        if (!$this->status->canApprove()) {
            throw \App\Exceptions\InvalidQuoteStateTransitionException::cannotApprove($this->status->value);
        }

        $this->update(['status' => QuoteStatus::Approved]);
    }

    /**
     * Reject the quote.
     *
     * @throws \App\Exceptions\InvalidQuoteStateTransitionException
     */
    public function reject(): void
    {
        if (!$this->status->canReject()) {
            throw \App\Exceptions\InvalidQuoteStateTransitionException::cannotReject($this->status->value);
        }

        $this->update(['status' => QuoteStatus::Rejected]);
    }

    /**
     * Duplicate the quote (creates a new draft).
     */
    public function duplicate(): Quote
    {
        $newQuote = $this->replicate(['quote_number', 'status', 'created_by']);
        $newQuote->status = QuoteStatus::Draft;
        $newQuote->created_by = \Illuminate\Support\Facades\Auth::id();
        $newQuote->save();

        foreach ($this->lines as $line) {
            $newLine = $line->replicate();
            $newLine->quote_id = $newQuote->id;
            $newLine->save();
        }

        return $newQuote;
    }

    /**
     * Get eager loading relations for listing.
     */
    public static function withRelations(): Builder
    {
        return static::with([
            'customer:id,name,code',
            'originPort:id,code,name',
            'destinationPort:id,code,name',
            'transportMode:id,code,name',
            'serviceType:id,code,name',
            'currency:id,code,symbol',
        ]);
    }
}
