<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteLine;
use Illuminate\Support\Facades\DB;

/**
 * Service for calculating and persisting quote pricing.
 *
 * This is the single source of truth for quote total calculations.
 *
 * ARCHITECTURE NOTES:
 * -------------------
 * This service is designed for future extension. Current implementation
 * calculates totals from lines. Future features can be added via:
 *
 * 1. SURCHARGES (Fuel, Security, etc.)
 *    - Add `quote_surcharges` table
 *    - Extend calculateTotals() to include surcharges
 *    - Example: fuel_surcharge = subtotal * fuel_percentage
 *
 * 2. TAXES (Multiple tax rates, jurisdictions)
 *    - Currently uses line-level tax_rate
 *    - Can extend with tax_rules table for jurisdiction-based taxes
 *    - Hook: calculateTaxes() method
 *
 * 3. DISCOUNTS (Quote-level discounts)
 *    - Add discount_percent/discount_amount to quotes table
 *    - Apply after subtotal, before taxes
 *    - Hook: applyDiscounts() method
 *
 * 4. RATE INTEGRATION
 *    - Use findApplicableRate() + applyRate() to auto-populate freight
 *    - Hook: already implemented in QuoteCalculationService
 *
 * 5. CURRENCY CONVERSION
 *    - Store base_currency_total for reporting
 *    - Apply exchange rate from currencies table
 *    - Hook: convertCurrency() method
 */
class QuotePricingService
{
    /**
     * Calculate totals for a quote (without persisting).
     *
     * @return array{subtotal: float, tax_amount: float, total_amount: float, line_count: int}
     */
    public function calculateTotals(Quote $quote): array
    {
        // Ensure lines are loaded
        if (!$quote->relationLoaded('lines')) {
            $quote->load('lines');
        }

        $subtotal = 0.0;
        $taxAmount = 0.0;
        $lineCount = 0;

        foreach ($quote->lines as $line) {
            $lineData = $this->calculateLine($line);
            $subtotal += $lineData['net_amount'];
            $taxAmount += $lineData['tax_amount'];
            $lineCount++;
        }

        // EXTENSION POINT: Add surcharges here
        // $surcharges = $this->calculateSurcharges($quote, $subtotal);
        // $subtotal += $surcharges;

        // EXTENSION POINT: Apply quote-level discounts here
        // $discount = $this->calculateQuoteDiscount($quote, $subtotal);
        // $subtotal -= $discount;

        // EXTENSION POINT: Add additional taxes here
        // $additionalTax = $this->calculateAdditionalTaxes($quote, $subtotal);
        // $taxAmount += $additionalTax;

        return [
            'subtotal' => round($subtotal, 4),
            'tax_amount' => round($taxAmount, 4),
            'total_amount' => round($subtotal + $taxAmount, 4),
            'line_count' => $lineCount,
        ];
    }

    /**
     * Calculate a single line's amounts.
     *
     * @return array{gross_amount: float, discount_amount: float, net_amount: float, tax_amount: float, total: float}
     */
    public function calculateLine(QuoteLine $line): array
    {
        $grossAmount = (float) $line->quantity * (float) $line->unit_price;
        $discountAmount = $grossAmount * ((float) $line->discount_percent / 100);
        $netAmount = $grossAmount - $discountAmount;
        $taxAmount = $netAmount * ((float) $line->tax_rate / 100);

        return [
            'gross_amount' => round($grossAmount, 4),
            'discount_amount' => round($discountAmount, 4),
            'net_amount' => round($netAmount, 4),
            'tax_amount' => round($taxAmount, 4),
            'total' => round($netAmount + $taxAmount, 4),
        ];
    }

    /**
     * Recalculate and persist quote totals transactionally.
     */
    public function recalculateAndPersist(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            // Refresh lines from database
            $quote->load('lines');

            // Calculate new totals
            $totals = $this->calculateTotals($quote);

            // Update quote
            $quote->update([
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
            ]);

            return $quote->fresh();
        });
    }

    /**
     * Add a line and recalculate totals transactionally.
     */
    public function addLineAndRecalculate(
        Quote $quote,
        int $productServiceId,
        float $quantity,
        float $unitPrice,
        ?string $description = null,
        float $discountPercent = 0,
        float $taxRate = 0,
        ?float $unitCost = null,
    ): QuoteLine {
        return DB::transaction(function () use ($quote, $productServiceId, $quantity, $unitPrice, $description, $discountPercent, $taxRate, $unitCost) {
            // Create line (line_total calculated automatically by model boot)
            $line = $quote->lines()->create([
                'product_service_id' => $productServiceId,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'discount_percent' => $discountPercent,
                'tax_rate' => $taxRate,
                'line_total' => 0, // Will be calculated by boot
                'sort_order' => $quote->lines()->count(),
            ]);

            // Recalculate quote totals
            $this->recalculateAndPersist($quote);

            return $line;
        });
    }

    /**
     * Update a line and recalculate totals transactionally.
     */
    public function updateLineAndRecalculate(
        QuoteLine $line,
        array $data
    ): QuoteLine {
        return DB::transaction(function () use ($line, $data) {
            $line->update($data);

            // Recalculate quote totals
            $this->recalculateAndPersist($line->quote);

            return $line->fresh();
        });
    }

    /**
     * Remove a line and recalculate totals transactionally.
     */
    public function removeLineAndRecalculate(QuoteLine $line): void
    {
        DB::transaction(function () use ($line) {
            $quote = $line->quote;
            $line->delete();

            // Recalculate quote totals
            $this->recalculateAndPersist($quote);
        });
    }

    // ====================================================================
    // EXTENSION POINTS (for future implementation)
    // ====================================================================

    /**
     * Calculate surcharges (fuel, security, etc.)
     *
     * FUTURE: Load from quote_surcharges table or apply rules.
     *
     * @return float Total surcharge amount
     */
    // protected function calculateSurcharges(Quote $quote, float $subtotal): float
    // {
    //     // Example implementation:
    //     // $surcharges = $quote->surcharges;
    //     // return $surcharges->sum('amount');
    //     return 0.0;
    // }

    /**
     * Calculate quote-level discount.
     *
     * FUTURE: Apply percentage or fixed discounts.
     */
    // protected function calculateQuoteDiscount(Quote $quote, float $subtotal): float
    // {
    //     if ($quote->discount_percent > 0) {
    //         return $subtotal * ($quote->discount_percent / 100);
    //     }
    //     return $quote->discount_amount ?? 0.0;
    // }

    /**
     * Calculate additional taxes (customs, duties, etc.)
     *
     * FUTURE: Apply jurisdiction-based tax rules.
     */
    // protected function calculateAdditionalTaxes(Quote $quote, float $subtotal): float
    // {
    //     return 0.0;
    // }
}
