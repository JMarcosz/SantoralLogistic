<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'rate_percent',
        'country',
        'is_active',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function charges(): BelongsToMany
    {
        return $this->belongsToMany(Charge::class)
            ->withPivot('tax_amount')
            ->withTimestamps();
    }
}
