<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountingPeriod Model
 * 
 * Represents a monthly accounting period with open/closed status.
 */
class AccountingPeriod extends Model
{
    protected $fillable = [
        'year',
        'month',
        'status',
        'lock_date',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'lock_date' => 'date',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    protected $appends = [
        'display_name',
        'period_name',
    ];

    // Status constants
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    /**
     * Get the user who closed this period.
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get the user who reopened this period.
     */
    public function reopener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function scopeForDate($query, Carbon|string $date)
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $query->where('year', $carbon->year)
            ->where('month', $carbon->month);
    }

    public function scopeCurrentPeriod($query)
    {
        $now = Carbon::now();
        return $query->forDate($now);
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if period is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Check if period is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Check if a specific date can be posted to this period.
     * 
     * @param Carbon|string $date
     * @return bool
     */
    public function canPostDate(Carbon|string $date): bool
    {
        if ($this->isClosed()) {
            return false;
        }

        // Check lock_date if set
        if ($this->lock_date) {
            $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
            return $carbon->greaterThanOrEqualTo($this->lock_date);
        }

        return true;
    }

    /**
     * Get period name (e.g., "2025-01", "Enero 2025").
     */
    public function getPeriodNameAttribute(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    /**
     * Get human-readable period name.
     */
    public function getDisplayNameAttribute(): string
    {
        $monthNames = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $monthNames[$this->month] . ' ' . $this->year;
    }

    /**
     * Get start date of period.
     */
    public function getStartDateAttribute(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfDay();
    }

    /**
     * Get end date of period.
     */
    public function getEndDateAttribute(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->endOfMonth()->endOfDay();
    }

    /**
     * Find or create period for a given date.
     * 
     * @param Carbon|string $date
     * @return static
     */
    public static function findOrCreateForDate(Carbon|string $date): static
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return static::firstOrCreate(
            [
                'year' => $carbon->year,
                'month' => $carbon->month,
            ],
            [
                'status' => self::STATUS_OPEN,
            ]
        );
    }

    /**
     * Get all periods for a year.
     */
    public static function getYearPeriods(int $year): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('year', $year)
            ->orderBy('month')
            ->get();
    }
}
