<?php

namespace App\Services;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating DGII (Dirección General de Impuestos Internos) reports
 * for Dominican Republic tax compliance.
 * 
 * Formats:
 * - 607: Sales/Income report
 * - 608: Cancelled invoices report
 */
class DgiiExportService
{
    /**
     * Tax ID Types for DGII
     */
    const TAX_ID_TYPE_RNC = '1';
    const TAX_ID_TYPE_CEDULA = '2';
    const TAX_ID_TYPE_PASSPORT = '3';

    /**
     * Income Types for 607
     */
    const INCOME_TYPE_OPERATIONS = '01'; // Ingresos por operaciones (normal)
    const INCOME_TYPE_FINANCIAL = '02';  // Ingresos financieros
    const INCOME_TYPE_EXTRAORDINARY = '03'; // Ingresos extraordinarios
    const INCOME_TYPE_LEASE = '04';      // Ingresos por arrendamientos
    const INCOME_TYPE_SALE_ASSETS = '05'; // Venta de activos depreciables
    const INCOME_TYPE_OTHER = '06';      // Otros ingresos

    /**
     * Payment Methods for 607
     */
    const PAYMENT_CASH = '01';           // Efectivo
    const PAYMENT_CHECK = '02';          // Cheques/Transferencias/Depósitos
    const PAYMENT_CARD = '03';           // Tarjeta Crédito/Débito
    const PAYMENT_CREDIT = '04';         // Venta a Crédito
    const PAYMENT_SWAP = '05';           // Permuta
    const PAYMENT_CREDIT_NOTE = '06';    // Nota de Crédito
    const PAYMENT_MIXED = '07';          // Mixto

    /**
     * Cancellation Types for 608
     */
    const CANCELLATION_DETERIORATION = '01';    // Deterioro de Factura Pre-impresa
    const CANCELLATION_PRINT_ERROR = '02';      // Errores de Impresión (Factura Pre-impresa)
    const CANCELLATION_DEFECTIVE_PRINT = '03';  // Impresión Defectuosa
    const CANCELLATION_CORRECTION = '04';       // Corrección de la Información
    const CANCELLATION_PRODUCT_CHANGE = '05';   // Cambio de Producto
    const CANCELLATION_PRODUCT_RETURN = '06';   // Devolución de Producto
    const CANCELLATION_PRODUCT_OMISSION = '07'; // Omisión de Productos
    const CANCELLATION_SEQUENCE_ERROR = '08';   // Errores en Secuencia de NCF
    const CANCELLATION_CEASE_OPERATIONS = '09'; // Por Cese de Operaciones
    const CANCELLATION_LOSS_THEFT = '10';       // Pérdida o Hurto de Talonario(s)

    /**
     * Threshold for mandatory RNC/Cedula (RD$)
     */
    const RNC_REQUIRED_THRESHOLD = 250000.00;

    /**
     * Generate 607 report (Sales/Income) for DGII.
     * 
     * @param \DateTimeInterface $periodStart Start of period
     * @param \DateTimeInterface $periodEnd End of period
     * @return string TXT file content (pipe-delimited)
     */
    public function generate607(\DateTimeInterface $periodStart, \DateTimeInterface $periodEnd): string
    {
        $invoices = $this->getIssuedInvoices($periodStart, $periodEnd);

        // Log export generation
        Log::channel('fiscal')->info('DGII 607 report generated', [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'invoice_count' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'user_id' => auth()->id(),
        ]);

        if ($invoices->isEmpty()) {
            return ''; // Empty file for periods with no invoices
        }

        $lines = $invoices->map(function (Invoice $invoice) {
            return $this->format607Line($invoice);
        });

        return $lines->implode("\n");
    }

    /**
     * Generate 608 report (Cancelled Invoices) for DGII.
     * 
     * @param \DateTimeInterface $periodStart Start of period
     * @param \DateTimeInterface $periodEnd End of period
     * @return string TXT file content (pipe-delimited)
     */
    public function generate608(\DateTimeInterface $periodStart, \DateTimeInterface $periodEnd): string
    {
        $invoices = $this->getCancelledInvoices($periodStart, $periodEnd);

        // Log export generation
        Log::channel('fiscal')->info('DGII 608 report generated', [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'cancelled_invoice_count' => $invoices->count(),
            'user_id' => auth()->id(),
        ]);

        if ($invoices->isEmpty()) {
            return ''; // Empty file for periods with no cancellations
        }

        $lines = $invoices->map(function (Invoice $invoice) {
            return $this->format608Line($invoice);
        });

        return $lines->implode("\n");
    }

    /**
     * Get issued invoices for period.
     */
    protected function getIssuedInvoices(\DateTimeInterface $start, \DateTimeInterface $end): Collection
    {
        return Invoice::with('customer')
            ->where('status', Invoice::STATUS_ISSUED)
            ->whereBetween('issue_date', [
                Carbon::instance($start)->format('Y-m-d'),
                Carbon::instance($end)->format('Y-m-d')
            ])
            ->orderBy('issue_date')
            ->orderBy('ncf')
            ->get();
    }

    /**
     * Get cancelled invoices for period.
     */
    protected function getCancelledInvoices(\DateTimeInterface $start, \DateTimeInterface $end): Collection
    {
        return Invoice::with('customer')
            ->where('status', Invoice::STATUS_CANCELLED)
            ->whereBetween('cancelled_at', [
                Carbon::instance($start)->startOfDay(),
                Carbon::instance($end)->endOfDay()
            ])
            ->orderBy('cancelled_at')
            ->orderBy('ncf')
            ->get();
    }

    /**
     * Format a single 607 line (pipe-delimited).
     * 
     * Format: RNC|TipoID|NCF|NCFModificado|TipoIngreso|FechaComprobante|RetencionRenta|
     *         MontoFacturado|ITBISFacturado|ITBISRetenido|ITBISPercibido|RetencionRentaPercibido|
     *         ISC|OtrosImpuestos|MontoPropinaLegal|FormaPago
     */
    protected function format607Line(Invoice $invoice): string
    {
        $customer = $invoice->customer;

        // Validate RNC/Cedula for amounts > RD$250,000
        $rnc = $this->formatTaxId($customer, $invoice->total_amount);
        $taxIdType = $this->mapTaxIdType($customer->tax_id_type);
        $ncf = $this->formatNcf($invoice->ncf);
        $ncfModified = ''; // Empty for MVP (credit/debit notes future)
        $incomeType = self::INCOME_TYPE_OPERATIONS; // Default: normal operations
        $issueDate = $this->formatDate($invoice->issue_date);
        $rentRetention = $this->formatAmount(0.00); // MVP: no retentions
        $invoicedAmount = $this->formatAmount($invoice->taxable_amount); // Base imponible
        $itbisBilled = $this->formatAmount($invoice->tax_amount); // ITBIS (18%)
        $itbisRetained = $this->formatAmount(0.00); // MVP: not applicable
        $itbisPerceived = $this->formatAmount(0.00); // Not enabled by DGII
        $rentRetentionPerceived = $this->formatAmount(0.00); // Not enabled
        $isc = $this->formatAmount(0.00); // Selective consumption tax (MVP: 0)
        $otherTaxes = $this->formatAmount(0.00); // Other taxes (MVP: 0)
        $legalTip = $this->formatAmount(0.00); // Legal tip 10% (MVP: 0)
        $paymentMethod = self::PAYMENT_CASH; // Default: cash (can be enhanced)

        return implode('|', [
            $rnc,
            $taxIdType,
            $ncf,
            $ncfModified,
            $incomeType,
            $issueDate,
            $rentRetention,
            $invoicedAmount,
            $itbisBilled,
            $itbisRetained,
            $itbisPerceived,
            $rentRetentionPerceived,
            $isc,
            $otherTaxes,
            $legalTip,
            $paymentMethod,
        ]);
    }

    /**
     * Format a single 608 line (pipe-delimited).
     * 
     * Format: NCF|FechaComprobante|TipoAnulacion
     */
    protected function format608Line(Invoice $invoice): string
    {
        $ncf = $this->formatNcf($invoice->ncf);
        $issueDate = $this->formatDate($invoice->issue_date);

        // Determine cancellation type from cancellation_reason or use default
        $cancellationType = $this->mapCancellationType($invoice->cancellation_reason);

        return implode('|', [
            $ncf,
            $issueDate,
            $cancellationType,
        ]);
    }

    /**
     * Format tax ID (RNC/Cedula/Passport).
     * 
     * DGII Validation: For invoices > RD$250,000, RNC/Cedula is mandatory.
     */
    protected function formatTaxId($customer, float $totalAmount): string
    {
        // If amount > threshold and no tax_id, this should have been validated earlier
        if ($totalAmount > self::RNC_REQUIRED_THRESHOLD && empty($customer->tax_id)) {
            Log::warning("Invoice exceeds RD$250,000 but customer {$customer->id} has no tax_id");
        }

        return $customer->tax_id ?? '';
    }

    /**
     * Map tax_id_type to DGII code.
     */
    protected function mapTaxIdType(?string $taxIdType): string
    {
        return match (strtolower($taxIdType ?? '')) {
            'rnc' => self::TAX_ID_TYPE_RNC,
            'cedula', 'cédula' => self::TAX_ID_TYPE_CEDULA,
            'passport', 'pasaporte' => self::TAX_ID_TYPE_PASSPORT,
            default => self::TAX_ID_TYPE_RNC, // Default to RNC
        };
    }

    /**
     * Format NCF (remove any dashes/spaces, uppercase).
     */
    protected function formatNcf(string $ncf): string
    {
        // DGII expects NCF without dashes (e.g., B0100000000005)
        return strtoupper(str_replace(['-', ' '], '', $ncf));
    }

    /**
     * Format date for DGII (DD/MM/YYYY).
     */
    protected function formatDate($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    /**
     * Format amount for DGII (always 2 decimals, dot separator).
     */
    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Map cancellation reason to DGII cancellation type code.
     */
    protected function mapCancellationType(?string $reason): string
    {
        if (empty($reason)) {
            return self::CANCELLATION_CORRECTION; // Default: correction
        }

        // Simple keyword matching (can be enhanced)
        $reasonLower = strtolower($reason);

        if (str_contains($reasonLower, 'error') || str_contains($reasonLower, 'correcc')) {
            return self::CANCELLATION_CORRECTION;
        }

        if (str_contains($reasonLower, 'devol')) {
            return self::CANCELLATION_PRODUCT_RETURN;
        }

        if (str_contains($reasonLower, 'cambio')) {
            return self::CANCELLATION_PRODUCT_CHANGE;
        }

        if (str_contains($reasonLower, 'secuencia')) {
            return self::CANCELLATION_SEQUENCE_ERROR;
        }

        // Default
        return self::CANCELLATION_CORRECTION;
    }


    /**
     * Get statistics for a reporting period.
     * 
     * @param \DateTimeInterface $start Start of period
     * @param \DateTimeInterface $end End of period
     * @return array Statistics for 607 and 608 reports
     */
    public function getStatistics(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $issuedInvoices = $this->getIssuedInvoices($start, $end);
        $cancelledInvoices = $this->getCancelledInvoices($start, $end);

        return [
            '607' => [
                'count' => $issuedInvoices->count(),
                'total_amount' => (float) $issuedInvoices->sum('total_amount'),
                'tax_amount' => (float) $issuedInvoices->sum('tax_amount'),
                'has_data' => $issuedInvoices->count() > 0,
            ],
            '608' => [
                'count' => $cancelledInvoices->count(),
                'total_amount' => (float) $cancelledInvoices->sum('total_amount'),
                'has_data' => $cancelledInvoices->count() > 0,
            ],
            'period' => [
                'start' => Carbon::instance($start)->format('Y-m-d'),
                'end' => Carbon::instance($end)->format('Y-m-d'),
                'display' => Carbon::instance($start)->translatedFormat('F Y'),
            ],
        ];
    }
    /**
     * Validate invoices before export (optional pre-check).
     * 
     * @return array Validation errors
     */
    public function validateInvoicesForExport(Collection $invoices): array
    {
        $errors = [];

        foreach ($invoices as $invoice) {
            // Check NCF format (should be 11-13 characters after removing dashes)
            $ncfClean = $this->formatNcf($invoice->ncf);
            if (strlen($ncfClean) < 11 || strlen($ncfClean) > 13) {
                $errors[] = "Invoice {$invoice->number}: NCF '{$invoice->ncf}' has invalid length";
            }

            // Check customer tax_id for high-value invoices
            if ($invoice->total_amount > self::RNC_REQUIRED_THRESHOLD && empty($invoice->customer->tax_id)) {
                $errors[] = "Invoice {$invoice->number}: RNC/Cédula required for amounts > RD$250,000";
            }

            // Check for required fields
            if (empty($invoice->ncf)) {
                $errors[] = "Invoice {$invoice->number}: Missing NCF";
            }

            if (empty($invoice->issue_date)) {
                $errors[] = "Invoice {$invoice->number}: Missing issue date";
            }
        }

        return $errors;
    }
}
