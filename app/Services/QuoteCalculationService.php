<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Rate;
use App\Models\ProductService;
use App\Enums\ChargeBasis;

/**
 * Service for calculating quote totals and applying rates.
 */
class QuoteCalculationService
{
    /**
     * Calculate line total (before tax).
     */
    public function calculateLineTotal(QuoteLine $line): float
    {
        $subtotal = (float) $line->quantity * (float) $line->unit_price;
        $discount = $subtotal * ((float) $line->discount_percent / 100);

        return $subtotal - $discount;
    }

    /**
     * Calculate all quote totals from lines.
     *
     * @return array{subtotal: float, tax_amount: float, total_amount: float}
     */
    public function calculateQuoteTotals(Quote $quote): array
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($quote->lines as $line) {
            $lineNet = $this->calculateLineTotal($line);
            $lineTax = $lineNet * ((float) $line->tax_rate / 100);

            $subtotal += $lineNet;
            $taxAmount += $lineTax;
        }

        return [
            'subtotal' => round($subtotal, 4),
            'tax_amount' => round($taxAmount, 4),
            'total_amount' => round($subtotal + $taxAmount, 4),
        ];
    }

    /**
     * Apply a rate to a quote as a freight line.
     *
     * @param Quote $quote The quote to add the freight line to
     * @param Rate $rate The rate to apply
     * @param ProductService|null $freightProduct Optional freight product (will look up by code 'FREIGHT' if not provided)
     * @return QuoteLine|null The created line or null if not applicable
     */
    public function applyRate(Quote $quote, Rate $rate, ?ProductService $freightProduct = null): ?QuoteLine
    {
        // For now, only handle per_shipment rates
        if ($rate->charge_basis !== ChargeBasis::PerShipment) {
            // Future: Handle per_kg/per_cbm with cargo details
            return null;
        }

        // Get or find freight product
        $freightProduct = $freightProduct ?? ProductService::where('code', 'FREIGHT')->first();

        if (!$freightProduct) {
            // Can't apply rate without a freight product
            return null;
        }

        // Calculate the price (use min_amount if base is lower)
        $price = max((float) $rate->base_amount, (float) ($rate->min_amount ?? 0));

        // Create the freight line
        return $quote->addLine(
            $freightProduct,
            quantity: 1,
            unitPrice: $price,
            description: "Freight: {$quote->lane}"
        );
    }

    /**
     * Find and suggest a rate for a quote's lane.
     */
    public function findRateForQuote(Quote $quote): ?Rate
    {
        return Rate::findForLane(
            $quote->origin_port_id,
            $quote->destination_port_id,
            $quote->transport_mode_id,
            $quote->service_type_id
        );
    }

    /**
     * Calculate chargeable weight (dimensional weight vs actual weight).
     * Formula: Volume (CBM) * 167 for air, * 1000 for ocean
     */
    public function calculateChargeableWeight(Quote $quote): ?float
    {
        if (!$quote->total_weight_kg || !$quote->total_volume_cbm) {
            return null;
        }

        // Dimensional weight factor depends on mode
        $factor = match ($quote->transportMode?->code) {
            'AIR' => 167,      // CBM to dimensional kg for air
            'OCEAN' => 1000,   // CBM to dimensional kg for ocean
            default => 167,
        };

        $dimensionalWeight = (float) $quote->total_volume_cbm * $factor;

        return max((float) $quote->total_weight_kg, $dimensionalWeight);
    }
}
