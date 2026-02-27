<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * JournalEntryLine Model
 * 
 * Represents a single line in a journal entry (debit or credit).
 */
class JournalEntryLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'description',
        'currency_code',
        'exchange_rate',
        'debit',
        'credit',
        'base_debit',
        'base_credit',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'debit' => 'decimal:4',
        'credit' => 'decimal:4',
        'base_debit' => 'decimal:4',
        'base_credit' => 'decimal:4',
    ];

    // ========== Boot ==========

    protected static function booted(): void
    {
        // Auto-calculate base amounts when saving
        static::saving(function (JournalEntryLine $line) {
            $rate = $line->exchange_rate ?: 1;
            $line->base_debit = bcmul((string) $line->debit, (string) $rate, 4);
            $line->base_credit = bcmul((string) $line->credit, (string) $rate, 4);
        });
    }

    // ========== Relationships ==========

    /**
     * Get the parent journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the account for this line.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // ========== Accessors ==========

    /**
     * Get the net amount (debit - credit) in original currency.
     */
    public function getNetAmountAttribute(): float
    {
        return (float) bcsub((string) $this->debit, (string) $this->credit, 4);
    }

    /**
     * Get the net amount in base currency.
     */
    public function getBaseNetAmountAttribute(): float
    {
        return (float) bcsub((string) $this->base_debit, (string) $this->base_credit, 4);
    }

    /**
     * Check if this is a debit line.
     */
    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Check if this is a credit line.
     */
    public function isCredit(): bool
    {
        return $this->credit > 0;
    }
}
