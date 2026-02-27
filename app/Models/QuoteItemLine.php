<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteItemLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_item_id',
        'pieces',
        'description',
        'weight_kg',
        'volume_cbm',
        'marks_numbers',
        'hs_code',
    ];

    protected $casts = [
        'weight_kg' => 'decimal:3',
        'volume_cbm' => 'decimal:3',
    ];

    public function item(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(QuoteItem::class, 'quote_item_id');
    }
}
