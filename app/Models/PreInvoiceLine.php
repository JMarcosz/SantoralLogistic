<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_invoice_id',
        'charge_id',
        'code',
        'description',
        'qty',
        'unit_price',
        'amount',
        'tax_amount',
        'is_taxable',
        'tax_rate',
        'currency_code',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'is_taxable' => 'boolean',
        'tax_rate' => 'decimal:2',
    ];

    public function preInvoice(): BelongsTo
    {
        return $this->belongsTo(PreInvoice::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }
}
