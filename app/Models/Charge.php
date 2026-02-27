<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Charge extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_order_id',
        'quote_id',
        'code',
        'description',
        'charge_type', // freight, surcharge, tax, other
        'basis', // flat, per_kg, per_cbm, per_shipment
        'currency_code',
        'unit_price',
        'qty',
        'amount',
        'is_tax_included',
        'is_manual',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'qty' => 'decimal:4',
        'amount' => 'decimal:4',
        'is_tax_included' => 'boolean',
        'is_manual' => 'boolean',
    ];

    public function shippingOrder(): BelongsTo
    {
        return $this->belongsTo(ShippingOrder::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class)
            ->withPivot('tax_amount')
            ->withTimestamps();
    }

    /**
     * Get the revenue account for accounting purposes.
     * When null, the default revenue account from accounting settings is used.
     */
    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }
}
