<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Service for generating Quote PDFs.
 */
class QuotePdfService
{
    public function __construct(
        protected TermsResolverService $termsResolver,
    ) {}

    /**
     * Generate a PDF for the given quote.
     */
    /**
     * Generate a PDF for the given quote.
     */
    public function generate(Quote $quote, string $lang = 'es', string $mode = 'standard'): \Barryvdh\DomPDF\PDF
    {
        // Set locale for this operation
        app()->setLocale($lang);

        // Load all relations
        $quote->load([
            'customer',
            'contact',
            'originPort',
            'destinationPort',
            'transportMode',
            'serviceType',
            'currency',
            'createdBy',
            'lines.productService',
            'paymentTerms',
            'footerTerms',
            'items.lines', // Load item lines for detailed view
            'shipper',
            'consignee',
            'division',
            'project',
            'issuingCompany',
            'carrier'
        ]);

        // Get company settings
        $company = CompanySetting::first();
        $companyLogo = $company?->getLogoBase64();

        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'company' => $company,
            'companyLogo' => $companyLogo,
            'paymentTermsText' => $this->termsResolver->getQuotePaymentTermsText($quote),
            'footerTermsText' => $this->termsResolver->getQuoteFooterTermsText($quote),
            'lang' => $lang,
            'mode' => $mode,
        ])->setPaper('letter', 'portrait');
    }

    /**
     * Generate and stream the PDF (inline display).
     */
    public function stream(Quote $quote, string $lang = 'es', string $mode = 'standard'): Response
    {
        $pdf = $this->generate($quote, $lang, $mode);
        $filename = "cotizacion-{$quote->quote_number}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Generate and download the PDF.
     */
    public function download(Quote $quote, string $lang = 'es', string $mode = 'standard'): Response
    {
        $pdf = $this->generate($quote, $lang, $mode);
        $filename = "cotizacion-{$quote->quote_number}.pdf";

        return $pdf->download($filename);
    }
}
