<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BankStatement Model
 * 
 * Represents an imported bank statement for reconciliation.
 */
class BankStatement extends Model
{
    protected $fillable = [
        'account_id',
        'statement_date',
        'period_start',
        'period_end',
        'reference',
        'description',
        'opening_balance',
        'closing_balance',
        'total_debits',
        'total_credits',
        'line_count',
        'reconciled_count',
        'status',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'decimal:4',
        'closing_balance' => 'decimal:4',
        'total_debits' => 'decimal:4',
        'total_credits' => 'decimal:4',
        'completed_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // ========== Scopes ==========

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // ========== Accessors ==========

    public function getReconciliationProgressAttribute(): float
    {
        if ($this->line_count === 0) {
            return 100;
        }
        return round(($this->reconciled_count / $this->line_count) * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_IN_PROGRESS => 'En Proceso',
            self::STATUS_COMPLETED => 'Completado',
            default => $this->status,
        };
    }

    // ========== Business Logic ==========

    /**
     * Recalculate totals from lines.
     */
    public function recalculateTotals(): void
    {
        $lines = $this->lines;

        $this->update([
            'total_debits' => $lines->where('amount', '<', 0)->sum(fn($l) => abs($l->amount)),
            'total_credits' => $lines->where('amount', '>=', 0)->sum('amount'),
            'line_count' => $lines->count(),
            'reconciled_count' => $lines->where('is_reconciled', true)->count(),
        ]);
    }

    /**
     * Mark statement as completed.
     */
    public function markCompleted(int $userId): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Check if fully reconciled.
     */
    public function isFullyReconciled(): bool
    {
        return $this->line_count > 0 && $this->line_count === $this->reconciled_count;
    }
}
