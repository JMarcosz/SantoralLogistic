<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency',
        'quote_currency',
        'rate',
        'rate_date',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'rate_date' => 'date',
    ];
}
