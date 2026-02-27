<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DailyBalance Model
 * 
 * Stores pre-calculated daily totals per account for fast ledger queries.
 * Updated when journal entries are posted or reversed.
 */
class DailyBalance extends Model
{
    protected $fillable = [
        'account_id',
        'date',
        'debit',
        'credit',
        'balance',
        'entry_count',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'balance' => 'decimal:4',
        'entry_count' => 'integer',
    ];

    /**
     * Get the account for this balance record.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope to filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $from, $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope to get balance before a specific date.
     */
    public function scopeBeforeDate($query, $date)
    {
        return $query->where('date', '<', $date);
    }

    /**
     * Get the net movement for the day (debit - credit for debit accounts, credit - debit for credit accounts).
     */
    public function getNetMovementAttribute(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }
}
