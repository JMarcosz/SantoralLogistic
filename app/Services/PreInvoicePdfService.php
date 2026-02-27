<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\PreInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Service for generating PreInvoice PDFs.
 */
class PreInvoicePdfService
{
    /**
     * Generate a PDF for the given pre-invoice.
     */
    public function generate(PreInvoice $preInvoice): \Barryvdh\DomPDF\PDF
    {
        // Load all relations
        $preInvoice->load([
            'customer',
            'shippingOrder.originPort', // Example relations from SO
            'shippingOrder.destinationPort',
            'lines',
        ]);

        // Get company settings
        $company = CompanySetting::first();
        $companyLogo = $company->getLogoBase64();

        return Pdf::loadView('pdf.pre-invoice', [
            'preInvoice' => $preInvoice,
            'company' => $company,
            'companyLogo' => $companyLogo,
        ])->setPaper('letter', 'portrait');
    }

    /**
     * Generate and stream the PDF (inline display).
     */
    public function stream(PreInvoice $preInvoice): Response
    {
        $pdf = $this->generate($preInvoice);
        $filename = "pre-factura-{$preInvoice->number}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Generate and download the PDF.
     */
    public function download(PreInvoice $preInvoice): Response
    {
        $pdf = $this->generate($preInvoice);
        $filename = "pre-factura-{$preInvoice->number}.pdf";

        return $pdf->download($filename);
    }
}
