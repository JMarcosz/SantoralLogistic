<?php

namespace App\Traits;

use App\Models\Quote;

/**
 * Generates unique quote numbers in format: QT-YYYY-NNNNNN
 * Example: QT-2024-000001
 */
trait GeneratesQuoteNumber
{
    /**
     * Generate the next quote number for the current year.
     */
    public static function generateQuoteNumber(): string
    {
        $year = now()->year;
        $prefix = "QT-{$year}-";

        // Get the last quote number for this year
        $lastQuote = Quote::withTrashed()
            ->where('quote_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTRING(quote_number, 9) AS INTEGER) DESC")
            ->first();

        if ($lastQuote) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastQuote->quote_number, 8);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return $prefix . str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Boot the trait to auto-generate quote number.
     */
    protected static function bootGeneratesQuoteNumber(): void
    {
        static::creating(function ($model) {
            if (empty($model->quote_number)) {
                $model->quote_number = static::generateQuoteNumber();
            }
        });
    }
}
