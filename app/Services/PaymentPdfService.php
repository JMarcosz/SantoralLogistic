<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Service for generating Payment Receipt PDFs.
 */
class PaymentPdfService
{
    /**
     * Generate a PDF receipt for the given payment.
     */
    public function generate(Payment $payment): \Barryvdh\DomPDF\PDF
    {
        // Load all relations
        $payment->load([
            'customer',
            'paymentMethod',
            'allocations.invoice',
            'creator',
            'postedBy',
        ]);

        // Get company settings
        $company = CompanySetting::first();
        $companyLogo = $company->getLogoBase64();

        return Pdf::loadView('pdf.payment-receipt', [
            'payment' => $payment,
            'company' => $company,
            'companyLogo' => $companyLogo,
        ])->setPaper('letter', 'portrait');
    }

    /**
     * Generate and stream the PDF (inline display).
     */
    public function stream(Payment $payment): Response
    {
        $pdf = $this->generate($payment);
        $filename = "recibo-{$payment->payment_number}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Generate and download the PDF.
     */
    public function download(Payment $payment): Response
    {
        $pdf = $this->generate($payment);
        $filename = "recibo-{$payment->payment_number}.pdf";

        return $pdf->download($filename);
    }
}
