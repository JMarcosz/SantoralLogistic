<?php

namespace App\Services;

use App\Models\PreInvoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting PreInvoices to external accounting systems.
 * 
 * Export Format (QuickBooks-ready):
 * 
 * HEADER ROW:
 * - pre_invoice_number: Unique invoice identifier (PI-YYYY-NNNNNN)
 * - customer_code: Customer's external code or tax_id
 * - customer_name: Full customer name
 * - issue_date: Invoice issue date (YYYY-MM-DD)
 * - due_date: Payment due date (YYYY-MM-DD)
 * - currency_code: ISO currency code (USD, DOP, etc.)
 * - subtotal_amount: Sum of line amounts before tax
 * - tax_amount: Total tax amount
 * - total_amount: Grand total
 * 
 * DETAIL ROWS (one per line item):
 * - pre_invoice_number: Parent invoice reference
 * - line_number: Sequential line number
 * - charge_code: Service/charge code
 * - description: Line item description
 * - qty: Quantity
 * - unit_price: Price per unit
 * - amount: Line total (qty * unit_price)
 * - tax_amount: Tax amount for this line
 */
class PreInvoiceExportService
{
    /**
     * Export headers for CSV.
     */
    protected array $headerColumns = [
        'record_type',
        'pre_invoice_number',
        'customer_code',
        'customer_name',
        'issue_date',
        'due_date',
        'currency_code',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
    ];

    protected array $lineColumns = [
        'record_type',
        'pre_invoice_number',
        'line_number',
        'charge_code',
        'description',
        'qty',
        'unit_price',
        'amount',
        'tax_amount',
    ];

    /**
     * Get exportable PreInvoices based on filters.
     */
    public function getExportableInvoices(array $filters = []): Collection
    {
        $query = PreInvoice::with(['customer', 'lines'])
            ->where('status', 'issued'); // Only export issued invoices

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Only non-exported filter (optional)
        if (!empty($filters['only_new']) && $filters['only_new']) {
            $query->whereNull('exported_at');
        }

        return $query->orderBy('issue_date', 'asc')
            ->orderBy('number', 'asc')
            ->get();
    }

    /**
     * Generate structured export data.
     */
    public function generateExportData(Collection $invoices): array
    {
        $data = [
            'meta' => [
                'export_date' => now()->toIso8601String(),
                'export_reference' => $this->generateExportReference(),
                'total_invoices' => $invoices->count(),
                'total_amount' => $invoices->sum('total_amount'),
            ],
            'invoices' => [],
        ];

        foreach ($invoices as $invoice) {
            $invoiceData = [
                'header' => [
                    'pre_invoice_number' => $invoice->number,
                    'customer_code' => $invoice->customer?->tax_id ?? $invoice->customer?->id,
                    'customer_name' => $invoice->customer?->name,
                    'issue_date' => $invoice->issue_date->format('Y-m-d'),
                    'due_date' => $invoice->due_date?->format('Y-m-d'),
                    'currency_code' => $invoice->currency_code,
                    'subtotal_amount' => (float) $invoice->subtotal_amount,
                    'tax_amount' => (float) $invoice->tax_amount,
                    'total_amount' => (float) $invoice->total_amount,
                ],
                'lines' => [],
            ];

            foreach ($invoice->lines as $index => $line) {
                $invoiceData['lines'][] = [
                    'line_number' => $index + 1,
                    'charge_code' => $line->code,
                    'description' => $line->description,
                    'qty' => (float) $line->qty,
                    'unit_price' => (float) $line->unit_price,
                    'amount' => (float) $line->amount,
                    'tax_amount' => (float) $line->tax_amount,
                ];
            }

            $data['invoices'][] = $invoiceData;
        }

        return $data;
    }

    /**
     * Export to JSON format.
     */
    public function exportToJson(Collection $invoices, bool $markAsExported = true): array
    {
        $data = $this->generateExportData($invoices);

        if ($markAsExported) {
            $this->markInvoicesAsExported($invoices, $data['meta']['export_reference']);
        }

        return $data;
    }

    /**
     * Export to CSV format (streamed response).
     */
    public function exportToCsv(Collection $invoices, bool $markAsExported = true): StreamedResponse
    {
        $exportReference = $this->generateExportReference();

        $response = new StreamedResponse(function () use ($invoices) {
            $handle = fopen('php://output', 'w');

            // Write header row
            $allColumns = array_merge($this->headerColumns, array_slice($this->lineColumns, 2));
            fputcsv($handle, $allColumns);

            foreach ($invoices as $invoice) {
                // Write invoice header row
                $headerRow = [
                    'HEADER', // record_type
                    $invoice->number,
                    $invoice->customer?->tax_id ?? $invoice->customer?->id,
                    $invoice->customer?->name,
                    $invoice->issue_date->format('Y-m-d'),
                    $invoice->due_date?->format('Y-m-d') ?? '',
                    $invoice->currency_code,
                    number_format((float) $invoice->subtotal_amount, 2, '.', ''),
                    number_format((float) $invoice->tax_amount, 2, '.', ''),
                    number_format((float) $invoice->total_amount, 2, '.', ''),
                    '',
                    '',
                    '',
                    '',
                    '',
                    '', // Empty line columns
                ];
                fputcsv($handle, $headerRow);

                // Write line items
                foreach ($invoice->lines as $index => $line) {
                    $lineRow = [
                        'DETAIL', // record_type
                        $invoice->number,
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '', // Empty header columns (except invoice number)
                        $index + 1, // line_number
                        $line->code,
                        $line->description,
                        number_format((float) $line->qty, 4, '.', ''),
                        number_format((float) $line->unit_price, 4, '.', ''),
                        number_format((float) $line->amount, 2, '.', ''),
                        number_format((float) $line->tax_amount, 2, '.', ''),
                    ];
                    fputcsv($handle, $lineRow);
                }
            }

            fclose($handle);
        });

        $filename = "pre-invoices-export-{$exportReference}.csv";

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        if ($markAsExported) {
            // Mark after generating (in a separate transaction)
            $this->markInvoicesAsExported($invoices, $exportReference);
        }

        return $response;
    }

    /**
     * Mark invoices as exported.
     */
    public function markInvoicesAsExported(Collection $invoices, string $reference): void
    {
        DB::transaction(function () use ($invoices, $reference) {
            PreInvoice::whereIn('id', $invoices->pluck('id'))
                ->update([
                    'exported_at' => now(),
                    'export_reference' => $reference,
                ]);
        });
    }

    /**
     * Generate a unique export reference.
     */
    protected function generateExportReference(): string
    {
        return 'EXP-' . now()->format('Ymd-His') . '-' . Str::random(4);
    }
}
