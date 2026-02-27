<?php

namespace App\Models;

use App\Enums\MilestoneCode;
use App\Enums\ShippingOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use App\Models\Term;
use App\Models\Charge;
use App\Models\PreInvoice;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'quote_id',
        'customer_id',
        'contact_id',
        'shipper_id',
        'consignee_id',
        'origin_port_id',
        'destination_port_id',
        'transport_mode_id',
        'service_type_id',
        'currency_id',
        'total_amount',
        'total_pieces',
        'total_weight_kg',
        'total_volume_cbm',
        'status',
        'is_active',
        'pickup_date',
        'delivery_date',
        'planned_departure_at',
        'planned_arrival_at',
        'actual_departure_at',
        'actual_arrival_at',
        'notes',
        'footer_terms_id',
        'footer_terms_snapshot',
        'created_by',
        // KPI fields
        'delivered_on_time',
        'delivered_in_full',
        'delivery_delay_days',
    ];

    protected $casts = [
        'status' => ShippingOrderStatus::class,
        'total_amount' => 'decimal:4',
        'total_pieces' => 'integer',
        'total_weight_kg' => 'decimal:3',
        'total_volume_cbm' => 'decimal:3',
        'is_active' => 'boolean',
        'pickup_date' => 'date',
        'delivery_date' => 'date',
        'planned_departure_at' => 'datetime',
        'planned_arrival_at' => 'datetime',
        'actual_departure_at' => 'datetime',
        'actual_arrival_at' => 'datetime',
        // KPI fields
        'delivered_on_time' => 'boolean',
        'delivered_in_full' => 'boolean',
        'delivery_delay_days' => 'integer',
    ];

    protected $attributes = [
        'status' => 'draft',
        'total_amount' => 0,
        'is_active' => true,
    ];

    // ========== Boot ==========

    protected static function booted(): void
    {
        static::creating(function (ShippingOrder $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * Generate the next order number for the current year.
     * Format: SO-YYYY-NNNNNN
     */
    public static function generateOrderNumber(): string
    {
        $year = now()->year;
        $prefix = "SO-{$year}-";

        $lastOrder = static::withTrashed()
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTRING(order_number, 9) AS INTEGER) DESC")
            ->first();

        if ($lastOrder) {
            $lastSequence = (int) substr($lastOrder->order_number, 8);
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

    /**
     * Shipper - operational party who ships the goods.
     */
    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'shipper_id');
    }

    /**
     * Consignee - operational party who receives the goods.
     */
    public function consignee(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'consignee_id');
    }

    /**
     * Ocean-specific shipment details (1:1).
     */
    public function oceanShipment(): HasOne
    {
        return $this->hasOne(OceanShipment::class);
    }

    /**
     * Air-specific shipment details (1:1).
     */
    public function airShipment(): HasOne
    {
        return $this->hasOne(AirShipment::class);
    }

    /**
     * Structured items/commodities (units).
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShippingOrderItem::class);
    }



    /**
     * Pickup orders associated with this shipping order.
     */
    public function pickupOrders(): HasMany
    {
        return $this->hasMany(PickupOrder::class);
    }

    /**
     * Delivery orders associated with this shipping order.
     */
    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    /**
     * Check if this shipping order has any P&D orders associated.
     */
    public function hasPDOrders(): bool
    {
        return $this->pickupOrders()->exists() || $this->deliveryOrders()->exists();
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

    public function milestones(): HasMany
    {
        return $this->hasMany(ShippingOrderMilestone::class)->chronological();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ShippingOrderDocument::class)->orderBy('created_at', 'desc');
    }

    public function publicLink(): HasOne
    {
        return $this->hasOne(ShippingOrderPublicLink::class);
    }

    /**
     * Get all inventory reservations for this shipping order.
     */
    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    /**
     * Check if this shipping order has any reserved inventory.
     */
    public function hasReservedInventory(): bool
    {
        return $this->inventoryReservations()->exists();
    }

    /**
     * Check if inventory can be reserved for this shipping order.
     * Only allowed in 'booked' or 'in_transit' status.
     */
    public function canReserveInventory(): bool
    {
        return in_array($this->status, [
            ShippingOrderStatus::Booked,
            ShippingOrderStatus::InTransit,
        ]);
    }

    /**
     * Get reserved quantities grouped by SKU.
     */
    public function reservedQtyBySku(): array
    {
        return $this->inventoryReservations()
            ->join('inventory_items', 'inventory_items.id', '=', 'inventory_reservations.inventory_item_id')
            ->selectRaw('inventory_items.sku, SUM(inventory_reservations.qty_reserved) as total_reserved')
            ->groupBy('inventory_items.sku')
            ->pluck('total_reserved', 'sku')
            ->toArray();
    }

    /**
     * Check if the order has an active public tracking link.
     */
    public function hasActivePublicLink(): bool
    {
        return $this->publicLink && $this->publicLink->isValid();
    }

    /**
     * Footer terms relationship.
     */
    public function footerTerms(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'terms_and_conditions_id');
    }

    /**
     * Charges associated with this shipping order.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class)->orderBy('sort_order', 'asc');
    }

    /**
     * PreInvoices generated from this shipping order.
     */
    public function preInvoices(): HasMany
    {
        return $this->hasMany(PreInvoice::class)->orderBy('created_at', 'desc');
    }

    /**
     * Fiscal invoices generated from this shipping order.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if this shipping order has been invoiced.
     */
    public function hasBeenInvoiced(): bool
    {
        return $this->invoices()->exists();
    }

    // ========== Scopes ==========

    public function scopeOfStatus(Builder $query, ShippingOrderStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof ShippingOrderStatus ? $status->value : $status);
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ShippingOrderStatus::Booked->value,
            ShippingOrderStatus::InTransit->value,
            ShippingOrderStatus::Arrived->value,
        ]);
    }

    // ========== Accessors ==========

    public function getLaneAttribute(): string
    {
        return "{$this->originPort?->code} → {$this->destinationPort?->code}";
    }

    /**
     * Recalculate totals based on structured items.
     */
    public function calculateTotalsFromItems(): void
    {
        $this->load('items.lines');

        $totalPieces = 0;
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($this->items as $item) {
            foreach ($item->lines as $line) {
                $totalPieces += $line->pieces;
                $totalWeight += $line->weight_kg;
                $totalVolume += $line->volume_cbm;
            }
        }

        $this->update([
            'total_pieces' => $totalPieces,
            'total_weight_kg' => $totalWeight,
            'total_volume_cbm' => $totalVolume,
        ]);
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
    public function canTransitionTo(ShippingOrderStatus $targetStatus): bool
    {
        return $this->status->canTransitionTo($targetStatus);
    }

    /**
     * Add a milestone to this shipping order.
     */
    public function addMilestone(
        string|MilestoneCode $code,
        ?string $label = null,
        ?\DateTimeInterface $happenedAt = null,
        ?string $location = null,
        ?string $remarks = null,
        ?int $createdBy = null
    ): ShippingOrderMilestone {
        $codeValue = $code instanceof MilestoneCode ? $code->value : $code;
        $labelValue = $label ?? ($code instanceof MilestoneCode ? $code->label() : $code);

        return $this->milestones()->create([
            'code' => $codeValue,
            'label' => $labelValue,
            'happened_at' => $happenedAt ?? now(),
            'location' => $location,
            'remarks' => $remarks,
            'created_by' => $createdBy ?? Auth::id(),
        ]);
    }

    /**
     * Get the latest milestone.
     */
    public function latestMilestone(): ?ShippingOrderMilestone
    {
        return $this->milestones()->recent()->first();
    }
}
