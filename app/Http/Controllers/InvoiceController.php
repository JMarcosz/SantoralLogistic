<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\CompanySetting;


use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    /**
     * Display a listing of fiscal invoices.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);
        $invoices = Invoice::query()
            ->with(['customer', 'shippingOrder'])
            ->when($request->customer_id, function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->from_date, function ($query, $fromDate) {
                $query->whereDate('issue_date', '>=', $fromDate);
            })
            ->when($request->to_date, function ($query, $toDate) {
                $query->whereDate('issue_date', '<=', $toDate);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ncf', 'like', "%{$search}%")
                        ->orWhere('number', 'like', "%{$search}%")
                        ->orWhereHas('shippingOrder', function ($soQuery) use ($search) {
                            $soQuery->where('order_number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('billing/Invoices/Index', [
            'invoices' => $invoices,
            'customers' => Customer::select('id', 'name', 'fiscal_name')
                ->orderBy('name')
                ->get(),
            'filters' => $request->only(['customer_id', 'status', 'from_date', 'to_date', 'search']),
        ]);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);
        $invoice->load(['customer', 'shippingOrder', 'lines', 'preInvoice']);

        // Look up journal entry for this invoice
        $journalEntryId = \App\Models\JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->value('id');

        return Inertia::render('billing/Invoices/Show', [
            'invoice' => array_merge($invoice->toArray(), [
                'journal_entry_id' => $journalEntryId,
            ]),
            'canCancel' => $invoice->status === Invoice::STATUS_ISSUED,
            'canPrint' => true,
        ]);
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('cancel', $invoice);
        // Validation
        if ($invoice->status !== Invoice::STATUS_ISSUED) {
            return back()->with('error', 'Solo se pueden cancelar facturas emitidas.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        // Update invoice
        $invoice->update([
            'status' => Invoice::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        // Log cancellation
        Log::channel('fiscal')->warning('Invoice cancelled', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'ncf' => $invoice->ncf,
            'customer_id' => $invoice->customer_id,
            'cancellation_reason' => $request->reason,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Factura cancelada exitosamente.');
    }

    /**
     * Generate PDF for invoice.
     */
    public function print(Invoice $invoice)
    {
        $this->authorize('print', $invoice);
        $invoice->load(['customer', 'lines', 'shippingOrder', 'preInvoice']);

        $company = CompanySetting::where('is_active', true)->first();
        $companyLogo = $company?->getLogoBase64();

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $company,
            'companyLogo' => $companyLogo,
        ]);

        return $pdf->stream("factura_{$invoice->number}.pdf");
    }

    /**
     * Email an invoice to a recipient.
     */
    public function email(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('email', $invoice);

        $request->validate([
            'email' => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        \Illuminate\Support\Facades\Mail::to($request->email)->send(
            new \App\Mail\InvoiceMailable(
                $invoice,
                $request->email,
                $request->message
            )
        );

        // Log email sent
        Log::channel('fiscal')->info('Invoice emailed', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'ncf' => $invoice->ncf,
            'recipient' => $request->email,
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Factura enviada por email exitosamente.');
    }

    /**
     * Batch export invoices as ZIP of PDFs.
     */
    public function batchExport(Request $request)
    {
        $this->authorize('export', Invoice::class);

        $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['exists:invoices,id'],
        ]);

        $invoices = Invoice::whereIn('id', $request->invoice_ids)
            ->with(['customer', 'lines', 'shippingOrder', 'preInvoice'])
            ->get();

        $company = CompanySetting::where('is_active', true)->first();
        $companyLogo = $company?->getLogoBase64();

        // Create temp directory if not exists
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Create ZIP
        $zip = new \ZipArchive();
        $zipFileName = 'facturas_' . now()->format('Y-m-d_His') . '.zip';
        $zipPath = $tempDir . '/' . $zipFileName;

        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            foreach ($invoices as $invoice) {
                $pdf = Pdf::loadView('pdf.invoice', [
                    'invoice' => $invoice,
                    'company' => $company,
                    'companyLogo' => $companyLogo,
                ]);
                $zip->addFromString(
                    "factura_{$invoice->number}.pdf",
                    $pdf->output()
                );
            }
            $zip->close();
        }

        // Log batch export  
        Log::channel('fiscal')->info('Batch invoice export', [
            'invoice_count' => $invoices->count(),
            'invoice_ids' => $request->invoice_ids,
            'user_id' => auth()->id(),
        ]);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
    private function getCompanyLogoBase64(?CompanySetting $company): ?string
    {
        if (!$company || !$company->logo_path) {
            return null;
        }

        try {
            $disk = config('filesystems.default', 'public');

            if (Storage::disk($disk)->exists($company->logo_path)) {
                $content = Storage::disk($disk)->get($company->logo_path);
                $mime = Storage::disk($disk)->mimeType($company->logo_path);
                $base64 = base64_encode($content);
                return "data:{$mime};base64,{$base64}";
            }
        } catch (\Exception $e) {
            Log::error('Failed to encode company logo for PDF', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
