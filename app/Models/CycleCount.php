<?php

namespace App\Models;

use App\Enums\CycleCountStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * CycleCount - Represents a cycle counting session for inventory verification.
 *
 * Used to compare physical inventory quantities against system quantities
 * and generate adjustment movements for reconciliation.
 */
class CycleCount extends Model
{
    protected $fillable = [
        'warehouse_id',
        'status',
        'reference',
        'scheduled_at',
        'completed_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => CycleCountStatus::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    // ========== Relationships ==========

    /**
     * Get the warehouse being counted.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this count.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all lines for this cycle count.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CycleCountLine::class);
    }

    // ========== State Helpers ==========

    /**
     * Check if the count is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === CycleCountStatus::Draft;
    }

    /**
     * Check if counting is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === CycleCountStatus::InProgress;
    }

    /**
     * Check if the count is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === CycleCountStatus::Completed;
    }

    /**
     * Check if the count is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Check if counting can be started.
     */
    public function canStart(): bool
    {
        return $this->status->canTransitionTo(CycleCountStatus::InProgress);
    }

    /**
     * Check if the count can be completed.
     */
    public function canComplete(): bool
    {
        return $this->status->canTransitionTo(CycleCountStatus::Completed);
    }

    /**
     * Check if the count can be cancelled.
     */
    public function canCancel(): bool
    {
        return $this->status->canTransitionTo(CycleCountStatus::Cancelled);
    }

    // ========== Computed Properties ==========

    /**
     * Get the total number of lines.
     */
    public function totalLines(): int
    {
        return $this->lines()->count();
    }

    /**
     * Get the number of lines that have been counted.
     */
    public function countedLinesCount(): int
    {
        return $this->lines()->whereNotNull('counted_qty')->count();
    }

    /**
     * Get the counting progress as a percentage.
     */
    public function countingProgress(): float
    {
        $total = $this->totalLines();
        if ($total <= 0) {
            return 100;
        }
        return round(($this->countedLinesCount() / $total) * 100, 2);
    }

    /**
     * Get the number of lines with differences.
     */
    public function linesWithDifferences(): int
    {
        return $this->lines()
            ->whereNotNull('difference_qty')
            ->where('difference_qty', '!=', 0)
            ->count();
    }

    /**
     * Get the total absolute difference quantity.
     */
    public function totalAbsoluteDifference(): float
    {
        return (float) $this->lines()
            ->whereNotNull('difference_qty')
            ->selectRaw('SUM(ABS(difference_qty)) as total')
            ->value('total') ?? 0;
    }

    // ========== Scopes ==========

    /**
     * Scope for counts by status.
     */
    public function scopeOfStatus($query, CycleCountStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for active (non-terminal) counts.
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            CycleCountStatus::Completed,
            CycleCountStatus::Cancelled,
        ]);
    }
}
