<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Quote;
use App\Models\ShippingOrder;
use App\Models\Term;

/**
 * Service to resolve and manage terms for Quotes and Shipping Orders.
 * 
 * This service handles:
 * - Resolving default terms from company settings
 * - Assigning terms to documents
 * - Capturing snapshots for legal traceability
 */
class TermsResolverService
{
    /**
     * Resolve and assign terms for a Quote.
     * 
     * @param Quote $quote The quote to assign terms to
     * @param int|null $paymentTermsId Optional specific payment terms ID
     * @param int|null $footerTermsId Optional specific footer terms ID
     */
    public function resolveForQuote(Quote $quote, ?int $paymentTermsId = null, ?int $footerTermsId = null): void
    {
        $companySettings = CompanySetting::first();

        // Resolve payment terms
        if ($paymentTermsId) {
            $quote->payment_terms_id = $this->validateTermId($paymentTermsId, Term::TYPE_PAYMENT);
        } elseif (!$quote->payment_terms_id && $companySettings?->default_payment_terms_id) {
            $quote->payment_terms_id = $companySettings->default_payment_terms_id;
        }

        // Resolve footer terms
        if ($footerTermsId) {
            $quote->footer_terms_id = $this->validateTermId($footerTermsId, Term::TYPE_QUOTE_FOOTER);
        } elseif (!$quote->footer_terms_id && $companySettings?->default_quote_terms_id) {
            $quote->footer_terms_id = $companySettings->default_quote_terms_id;
        }
    }

    /**
     * Resolve and assign terms for a Shipping Order.
     * 
     * @param ShippingOrder $shippingOrder The shipping order to assign terms to
     * @param int|null $footerTermsId Optional specific footer terms ID
     */
    public function resolveForShippingOrder(ShippingOrder $shippingOrder, ?int $footerTermsId = null): void
    {
        $companySettings = CompanySetting::first();

        // Resolve footer terms
        if ($footerTermsId) {
            $shippingOrder->footer_terms_id = $this->validateTermId($footerTermsId, Term::TYPE_SO_FOOTER);
        } elseif (!$shippingOrder->footer_terms_id && $companySettings?->default_so_terms_id) {
            $shippingOrder->footer_terms_id = $companySettings->default_so_terms_id;
        }
    }

    /**
     * Capture snapshots for a Quote's terms.
     * Only captures if snapshots are empty (never overwrites for legal traceability).
     */
    public function captureQuoteSnapshots(Quote $quote): void
    {
        // Capture payment terms snapshot
        if (!$quote->payment_terms_snapshot && $quote->payment_terms_id) {
            $term = Term::find($quote->payment_terms_id);
            if ($term) {
                $quote->payment_terms_snapshot = $term->body;
            }
        }

        // Capture footer terms snapshot
        if (!$quote->footer_terms_snapshot && $quote->footer_terms_id) {
            $term = Term::find($quote->footer_terms_id);
            if ($term) {
                $quote->footer_terms_snapshot = $term->body;
            }
        }
    }

    /**
     * Capture snapshots for a Shipping Order's terms.
     * Only captures if snapshots are empty (never overwrites for legal traceability).
     */
    public function captureShippingOrderSnapshots(ShippingOrder $shippingOrder): void
    {
        // Capture footer terms snapshot
        if (!$shippingOrder->footer_terms_snapshot && $shippingOrder->footer_terms_id) {
            $term = Term::find($shippingOrder->footer_terms_id);
            if ($term) {
                $shippingOrder->footer_terms_snapshot = $term->body;
            }
        }
    }

    /**
     * Get the display text for quote payment terms.
     * Uses snapshot if available, falls back to term body or company default.
     */
    public function getQuotePaymentTermsText(Quote $quote): ?string
    {
        // First priority: snapshot (frozen text)
        if ($quote->payment_terms_snapshot) {
            return $quote->payment_terms_snapshot;
        }

        // Second priority: linked term
        if ($quote->payment_terms_id) {
            $term = Term::find($quote->payment_terms_id);
            return $term?->body;
        }

        // Fallback: company default
        $companySettings = CompanySetting::first();
        return $companySettings?->defaultPaymentTerms?->body;
    }

    /**
     * Get the display text for quote footer terms.
     * Uses snapshot if available, falls back to term body or company default.
     */
    public function getQuoteFooterTermsText(Quote $quote): ?string
    {
        // First priority: snapshot (frozen text)
        if ($quote->footer_terms_snapshot) {
            return $quote->footer_terms_snapshot;
        }

        // Second priority: linked term
        if ($quote->footer_terms_id) {
            $term = Term::find($quote->footer_terms_id);
            return $term?->body;
        }

        // Fallback: company default
        $companySettings = CompanySetting::first();
        return $companySettings?->defaultQuoteTerms?->body;
    }

    /**
     * Get the display text for shipping order footer terms.
     * Uses snapshot if available, falls back to term body or company default.
     */
    public function getShippingOrderFooterTermsText(ShippingOrder $shippingOrder): ?string
    {
        // First priority: snapshot (frozen text)
        if ($shippingOrder->footer_terms_snapshot) {
            return $shippingOrder->footer_terms_snapshot;
        }

        // Second priority: linked term
        if ($shippingOrder->footer_terms_id) {
            $term = Term::find($shippingOrder->footer_terms_id);
            return $term?->body;
        }

        // Fallback: company default
        $companySettings = CompanySetting::first();
        return $companySettings?->defaultSoTerms?->body;
    }

    /**
     * Validate that a term ID exists and matches the expected type.
     * Returns the ID if valid, null otherwise.
     */
    protected function validateTermId(int $termId, string $expectedType): ?int
    {
        $term = Term::where('id', $termId)
            ->where('type', $expectedType)
            ->where('is_active', true)
            ->first();

        return $term?->id;
    }
}
