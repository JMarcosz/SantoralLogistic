<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\ShippingOrder;
use Illuminate\Support\Facades\DB;

/**
 * Service for converting approved Quotes to Shipping Orders.
 */
class QuoteConversionService
{
    public function __construct(
        protected TermsResolverService $termsResolver,
    ) {}

    /**
     * Convert an approved quote to a shipping order.
     * 
     * Note: Uses Option A for terms - assigns default SO terms from company settings,
     * NOT inherited from the Quote. This maintains separation between document types.
     *
     * @throws \InvalidArgumentException If quote is not approved
     * @throws \Exception If conversion fails
     */
    public function convertToShippingOrder(Quote $quote): ShippingOrder
    {
        // Validate quote is approved
        if ($quote->status !== QuoteStatus::Approved) {
            throw new \InvalidArgumentException(
                "Cannot convert quote {$quote->quote_number} to shipping order: status is '{$quote->status->value}', must be 'approved'."
            );
        }

        // Check if already converted
        $existingOrder = ShippingOrder::where('quote_id', $quote->id)->first();
        if ($existingOrder) {
            throw new \InvalidArgumentException(
                "Quote {$quote->quote_number} has already been converted to Shipping Order {$existingOrder->order_number}."
            );
        }

        return DB::transaction(function () use ($quote) {
            $shippingOrder = ShippingOrder::create([
                'quote_id' => $quote->id,
                'customer_id' => $quote->customer_id,
                'contact_id' => $quote->contact_id,
                'origin_port_id' => $quote->origin_port_id,
                'destination_port_id' => $quote->destination_port_id,
                'transport_mode_id' => $quote->transport_mode_id,
                'service_type_id' => $quote->service_type_id,
                'currency_id' => $quote->currency_id,
                'total_amount' => $quote->total_amount,
                'total_pieces' => $quote->total_pieces,
                'total_weight_kg' => $quote->total_weight_kg,
                'total_volume_cbm' => $quote->total_volume_cbm,
                'status' => 'draft',
                'notes' => "Converted from Quote {$quote->quote_number}",
                'created_by' => auth()->id(),
            ]);

            // Assign default SO terms from company settings (Option A)
            $this->termsResolver->resolveForShippingOrder($shippingOrder);
            $shippingOrder->save();

            // Convert QuoteLines to Charges for the ShippingOrder
            foreach ($quote->lines as $line) {
                $shippingOrder->charges()->create([
                    'code' => $line->productService?->code ?? 'SVC',
                    'description' => $line->description,
                    'charge_type' => 'freight', // Default type for quoted services
                    'basis' => 'flat',
                    'currency_code' => $quote->currency?->code ?? 'USD',
                    'unit_price' => $line->unit_price,
                    'qty' => $line->quantity,
                    'amount' => $line->line_total,
                    'is_tax_included' => $line->tax_rate > 0,
                    'is_manual' => false,
                    'sort_order' => $line->sort_order,
                ]);
            }

            return $shippingOrder;
        });
    }

    /**
     * Check if a quote can be converted.
     */
    public function canConvert(Quote $quote): bool
    {
        if ($quote->status !== QuoteStatus::Approved) {
            return false;
        }

        // Check if already converted
        return !ShippingOrder::where('quote_id', $quote->id)->exists();
    }

    /**
     * Get the shipping order for a quote if it exists.
     */
    public function getShippingOrderForQuote(Quote $quote): ?ShippingOrder
    {
        return ShippingOrder::where('quote_id', $quote->id)->first();
    }
}
