<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\ShippingOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Service for generating Shipping Order PDFs.
 */
class ShippingOrderPdfService
{
    public function __construct(
        protected TermsResolverService $termsResolver,
    ) {}

    /**
     * Generate a PDF for the given shipping order.
     */
    public function generate(ShippingOrder $shippingOrder): \Barryvdh\DomPDF\PDF
    {
        // Load all relations
        $shippingOrder->load([
            'customer',
            'contact',
            'originPort',
            'destinationPort',
            'transportMode',
            'serviceType',
            'currency',
            'quote',
            'milestones',
            'footerTerms',
        ]);

        // Get company settings
        $company = CompanySetting::first();
        $companyLogo = $company->getLogoBase64();

        // Get footer terms text (snapshot > linked term > company default)
        $footerTermsText = $this->termsResolver->getShippingOrderFooterTermsText($shippingOrder);

        return Pdf::loadView('pdf.shipping-order', [
            'order' => $shippingOrder,
            'company' => $company,
            'companyLogo' => $companyLogo,
            'footerTermsText' => $footerTermsText,
        ])->setPaper('letter', 'portrait');
    }

    /**
     * Generate and stream the PDF (inline display).
     */
    public function stream(ShippingOrder $shippingOrder): Response
    {
        $pdf = $this->generate($shippingOrder);
        $filename = "shipping-order-{$shippingOrder->order_number}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Generate and download the PDF.
     */
    public function download(ShippingOrder $shippingOrder): Response
    {
        $pdf = $this->generate($shippingOrder);
        $filename = "shipping-order-{$shippingOrder->order_number}.pdf";

        return $pdf->download($filename);
    }
}
