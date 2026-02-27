<?php

namespace App\Models;

use App\Enums\JournalEntryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * JournalEntry Model
 * 
 * Represents an accounting journal entry (asiento contable).
 */
class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number',
        'date',
        'description',
        'status',
        'source_type',
        'source_id',
        'created_by',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'reversal_of_entry_id',
    ];

    protected $casts = [
        'date' => 'date',
        'status' => JournalEntryStatus::class,
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    protected $appends = [
        'total_debit',
        'total_credit',
        'total_base_debit',
        'total_base_credit',
        'is_balanced',
    ];

    // ========== Boot ==========

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry) {
            if (empty($entry->entry_number)) {
                $entry->entry_number = static::generateEntryNumber();
            }

            if (empty($entry->created_by)) {
                $entry->created_by = auth()->id();
            }
        });
    }

    /**
     * Generate the next entry number for the current year.
     * Format: JE-YYYY-NNNNNN
     */
    public static function generateEntryNumber(): string
    {
        $year = now()->year;
        $prefix = "JE-{$year}-";

        $last = static::where('entry_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderBy('entry_number', 'desc')
            ->first();

        if (!$last) {
            return $prefix . '000001';
        }

        $sequence = (int) Str::after($last->entry_number, $prefix);
        $next = $sequence + 1;

        return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    // ========== Relationships ==========

    /**
     * Get the entry lines.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Get the source model (Invoice, Payment, etc.).
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who posted this entry.
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Get the user who reversed this entry.
     */
    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /**
     * Get the original entry if this is a reversal.
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_of_entry_id');
    }

    /**
     * Get the reversal entry if this entry was reversed.
     */
    public function reversalEntry(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'reversal_of_entry_id');
    }

    // ========== Scopes ==========

    public function scopeDraft($query)
    {
        return $query->where('status', JournalEntryStatus::Draft);
    }

    public function scopePosted($query)
    {
        return $query->where('status', JournalEntryStatus::Posted);
    }

    public function scopeReversed($query)
    {
        return $query->where('status', JournalEntryStatus::Reversed);
    }

    public function scopeByDate($query, $from, $to = null)
    {
        if ($to) {
            return $query->whereBetween('date', [$from, $to]);
        }

        return $query->whereDate('date', $from);
    }

    public function scopeBySource($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    public function scopeManual($query)
    {
        return $query->whereNull('source_type');
    }

    // ========== Accessors ==========

    /**
     * Get total debit in transaction currency.
     */
    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines->sum('debit');
    }

    /**
     * Get total credit in transaction currency.
     */
    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines->sum('credit');
    }

    /**
     * Get total debit in base currency.
     */
    public function getTotalBaseDebitAttribute(): float
    {
        return (float) $this->lines->sum('base_debit');
    }

    /**
     * Get total credit in base currency.
     */
    public function getTotalBaseCreditAttribute(): float
    {
        return (float) $this->lines->sum('base_credit');
    }

    /**
     * Check if entry is balanced (in base currency).
     */
    public function getIsBalancedAttribute(): bool
    {
        return bccomp(
            (string) $this->total_base_debit,
            (string) $this->total_base_credit,
            4
        ) === 0;
    }

    // ========== Business Logic ==========

    /**
     * Check if entry can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    /**
     * Check if entry can be posted.
     */
    public function canPost(): bool
    {
        return $this->status->canPost() && $this->is_balanced && $this->lines->count() >= 2;
    }

    /**
     * Check if entry can be reversed.
     */
    public function canReverse(): bool
    {
        return $this->status->canReverse();
    }

    /**
     * Check if entry can be deleted.
     */
    public function canDelete(): bool
    {
        return $this->status->canDelete();
    }
}
