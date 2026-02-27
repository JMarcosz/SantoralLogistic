<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BankStatementLine Model
 * 
 * Represents individual transactions from a bank statement for reconciliation.
 */
class BankStatementLine extends Model
{
    protected $fillable = [
        'bank_statement_id',
        'transaction_date',
        'value_date',
        'reference',
        'description',
        'amount',
        'running_balance',
        'transaction_type',
        'journal_entry_line_id',
        'payment_id',
        'is_reconciled',
        'reconciled_by',
        'reconciled_at',
        'reconciliation_notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:4',
        'running_balance' => 'decimal:4',
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    // ========== Relationships ==========

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    // ========== Scopes ==========

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeUnreconciled($query)
    {
        return $query->where('is_reconciled', false);
    }

    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('amount', '>=', 0);
    }

    // ========== Accessors ==========

    public function getIsDebitAttribute(): bool
    {
        return $this->amount < 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->amount >= 0;
    }

    public function getAbsoluteAmountAttribute(): float
    {
        return abs($this->amount);
    }

    // ========== Business Logic ==========

    /**
     * Match this line to a journal entry line.
     */
    public function matchToJournalLine(JournalEntryLine $journalLine, int $userId, ?string $notes = null): void
    {
        $this->update([
            'journal_entry_line_id' => $journalLine->id,
            'is_reconciled' => true,
            'reconciled_by' => $userId,
            'reconciled_at' => now(),
            'reconciliation_notes' => $notes,
        ]);

        // Also mark the journal line as reconciled
        $journalLine->update([
            'is_reconciled' => true,
            'reconciled_by' => $userId,
            'reconciled_at' => now(),
        ]);

        // Update statement totals
        $this->bankStatement->recalculateTotals();
    }

    /**
     * Match this line to a payment.
     */
    public function matchToPayment(Payment $payment, int $userId, ?string $notes = null): void
    {
        $this->update([
            'payment_id' => $payment->id,
            'is_reconciled' => true,
            'reconciled_by' => $userId,
            'reconciled_at' => now(),
            'reconciliation_notes' => $notes,
        ]);

        // Update statement totals
        $this->bankStatement->recalculateTotals();
    }

    /**
     * Unmatch/unreconcile this line.
     */
    public function unmatch(): void
    {
        // If matched to journal line, unreconcile it too
        if ($this->journal_entry_line_id) {
            $this->journalEntryLine?->update([
                'is_reconciled' => false,
                'reconciled_by' => null,
                'reconciled_at' => null,
            ]);
        }

        $this->update([
            'journal_entry_line_id' => null,
            'payment_id' => null,
            'is_reconciled' => false,
            'reconciled_by' => null,
            'reconciled_at' => null,
            'reconciliation_notes' => null,
        ]);

        // Update statement totals
        $this->bankStatement->recalculateTotals();
    }
}
