<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'ncf_type',
        'series',
        'ncf_from',
        'ncf_to',
        'current_ncf',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Only active sequences
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Sequences valid at a given date (defaults to now)
     */
    public function scopeValidAt($query, ?\Carbon\Carbon $date = null)
    {
        $date = $date ?? now();

        return $query->where('valid_from', '<=', $date)
            ->where('valid_to', '>=', $date);
    }

    /**
     * Scope: Filter by NCF type and optional series
     */
    public function scopeForType($query, string $ncfType, ?string $series = null)
    {
        $query->where('ncf_type', $ncfType);

        if ($series !== null) {
            $query->where('series', $series);
        } else {
            $query->whereNull('series');
        }

        return $query;
    }

    /**
     * Check if this sequence is exhausted (reached the end)
     */
    public function isExhausted(): bool
    {
        if ($this->current_ncf === null) {
            return false;
        }

        // Compare NCF strings: if current >= to, then exhausted
        return strcmp($this->current_ncf, $this->ncf_to) >= 0;
    }

    /**
     * Check if this sequence is currently valid (within date range)
     */
    public function isValidNow(): bool
    {
        $now = now()->startOfDay();

        return $this->valid_from->lte($now) && $this->valid_to->gte($now);
    }

    /**
     * Get the next available NCF in this sequence without saving
     * (used for validation and preview)
     */
    public function peekNextNcf(): string
    {
        // If no current_ncf, return the starting NCF
        if ($this->current_ncf === null) {
            return $this->ncf_from;
        }

        // Extract numeric suffix and increment
        return $this->incrementNcf($this->current_ncf);
    }

    /**
     * Increment an NCF string
     * Assumes NCF format: prefix + numeric suffix
     * Example: B01-00000000001 -> B01-00000000002
     */
    protected function incrementNcf(string $ncf): string
    {
        // Extract the numeric suffix using regex
        if (!preg_match('/^(.*)(\d+)$/', $ncf, $matches)) {
            throw new \InvalidArgumentException("Invalid NCF format: {$ncf}");
        }

        $prefix = $matches[1];
        $numeric = $matches[2];
        $width = strlen($numeric);

        // Increment the numeric part
        $nextNumeric = (int) $numeric + 1;

        // Pad with zeros to maintain the same width
        $paddedNumeric = str_pad((string) $nextNumeric, $width, '0', STR_PAD_LEFT);

        return $prefix . $paddedNumeric;
    }

    /**
     * Get total available NCF count in this range
     */
    public function totalAvailable(): int
    {
        preg_match('/(\d+)$/', $this->ncf_from, $fromMatches);
        preg_match('/(\d+)$/', $this->ncf_to, $toMatches);

        if (!$fromMatches || !$toMatches) {
            return 0;
        }

        return (int) $toMatches[1] - (int) $fromMatches[1] + 1;
    }

    /**
     * Get count of NCFs used from this range
     */
    public function usedCount(): int
    {
        if (!$this->current_ncf) {
            return 0;
        }

        preg_match('/(\d+)$/', $this->ncf_from, $fromMatches);
        preg_match('/(\d+)$/', $this->current_ncf, $currentMatches);

        if (!$fromMatches || !$currentMatches) {
            return 0;
        }

        return (int) $currentMatches[1] - (int) $fromMatches[1] + 1;
    }

    /**
     * Check if range is near exhaustion
     *
     * @param int $thresholdPercent Percentage threshold (default 80%)
     * @return bool
     */
    public function isNearExhaustion(int $thresholdPercent = 80): bool
    {
        $total = $this->totalAvailable();
        if ($total === 0) {
            return false;
        }

        $used = $this->usedCount();
        $percent = ($used / $total) * 100;

        return $percent >= $thresholdPercent;
    }

    /**
     * Check if range is near expiration
     *
     * @param int $days Days until expiration threshold (default 15)
     * @return bool
     */
    public function isNearExpiration(int $days = 15): bool
    {
        $daysUntilExpiration = $this->valid_to->diffInDays(now(), false);

        return $daysUntilExpiration >= 0 && $daysUntilExpiration <= $days;
    }

    /**
     * Check if a proposed NCF range overlaps with existing ranges.
     * 
     * This method validates that a new or updated fiscal sequence range
     * does not overlap with any existing active ranges for the same
     * ncf_type + series combination.
     * 
     * NCF format assumption: All NCFs have the same prefix and numeric suffix
     * with consistent padding (e.g., B01-00000000001). String comparison works
     * correctly for this format.
     * 
     * @param string $ncfType NCF type (e.g., 'B01', 'B02')
     * @param string|null $series Optional series identifier
     * @param string $ncfFrom Start of the proposed range
     * @param string $ncfTo End of the proposed range
     * @param int|null $excludeId ID of the fiscal sequence to exclude (for edits)
     * @return bool True if overlap exists, false otherwise
     */
    public static function hasOverlap(
        string $ncfType,
        ?string $series,
        string $ncfFrom,
        string $ncfTo,
        ?int $excludeId = null
    ): bool {
        // Build base query for same ncf_type + series
        $query = self::query()
            ->where('ncf_type', $ncfType)
            ->where('is_active', true);

        // Handle series: null must match null, non-null must match exact value
        if ($series === null) {
            $query->whereNull('series');
        } else {
            $query->where('series', $series);
        }

        // Exclude the current record if editing
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        // Check for overlapping ranges
        // Two ranges [A, B] and [C, D] overlap if:
        // - A <= D AND C <= B
        // Using string comparison which works for NCF format with consistent padding
        $overlapping = $query->where(function ($q) use ($ncfFrom, $ncfTo) {
            $q->where(function ($subQ) use ($ncfFrom, $ncfTo) {
                // Proposed range starts before or at existing range end
                // AND proposed range ends after or at existing range start
                $subQ->where('ncf_from', '<=', $ncfTo)
                    ->where('ncf_to', '>=', $ncfFrom);
            });
        })->exists();

        return $overlapping;
    }
}
