<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'pre_invoice_line_id',
        'code',
        'description',
        'qty',
        'unit_price',
        'amount',
        'tax_amount',
        'currency_code',
        'sort_order',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'sort_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function preInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(PreInvoiceLine::class);
    }
}
