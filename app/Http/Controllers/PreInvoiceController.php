<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PreInvoice;
use App\Models\ShippingOrder;
use App\Services\PaymentService;
use App\Services\PreInvoiceService;
use App\Services\PreInvoicePdfService;
use App\Services\PreInvoiceExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Customer;

class PreInvoiceController extends Controller
{
    public function __construct(
        protected PreInvoiceService $preInvoiceService,
        protected PreInvoicePdfService $pdfService,
        protected PreInvoiceExportService $exportService,
        protected PaymentService $paymentService,
        protected \App\Services\InvoiceService $invoiceService
    ) {}

    /**
     * List PreInvoices.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PreInvoice::class);

        $query = PreInvoice::with(['customer', 'shippingOrder']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('issue_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('issue_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('shippingOrder', function ($q) use ($search) {
                        $q->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        $preInvoices = $query
            ->orderBy('issue_date', 'desc')
            ->orderBy('number', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('billing/pre-invoices/index', [
            'preInvoices' => $preInvoices,
            'customers' => Customer::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'filters' => [
                'customer_id' => $request->customer_id,
                'status' => $request->status,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show PreInvoice details.
     */
    public function show(PreInvoice $preInvoice): Response
    {
        $this->authorize('view', $preInvoice);

        $preInvoice->load(['customer', 'shippingOrder', 'lines', 'payments.creator', 'payments.approver']);

        return Inertia::render('billing/pre-invoices/show', [
            'preInvoice' => $preInvoice,
            'paymentMethods' => Payment::METHODS,
            'can' => [
                'recordPayment' => request()->user()->can('billing.payments.create') && $preInvoice->isOpen(),
                'approvePayment' => request()->user()->can('billing.payments.approve'),
                'voidPayment' => request()->user()->can('billing.payments.void'),
                'generateInvoice' => request()->user()->can('invoices.create'),
            ],
        ]);
    }

    /**
     * Show the form for creating a manual PreInvoice.
     */
    public function create(): Response
    {
        $this->authorize('create', PreInvoice::class);

        $customers = \App\Models\Customer::select('id', 'name', 'code', 'tax_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currencies = \App\Models\Currency::select('id', 'code', 'symbol', 'name')
            ->orderBy('code')
            ->get();

        $defaultCurrency = \App\Models\Currency::where('is_default', true)->first();

        return Inertia::render('billing/pre-invoices/create', [
            'customers' => $customers,
            'currencies' => $currencies,
            'defaultCurrencyId' => $defaultCurrency?->id,
        ]);
    }

    /**
     * Store a new manual PreInvoice.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PreInvoice::class);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'currency_code' => 'required|string|size:3',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:5000',
            'lines' => 'required|array|min:1',
            'lines.*.code' => 'required|string|max:50',
            'lines.*.description' => 'required|string|max:255',
            'lines.*.qty' => 'required|numeric|min:0.0001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.is_taxable' => 'boolean',
            'lines.*.tax_rate' => 'nullable|numeric|min:0',
        ]);

        try {
            $preInvoice = $this->preInvoiceService->createManual($validated);
            return redirect()->route('pre-invoices.show', $preInvoice)
                ->with('success', 'Pre-factura creada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Create PreInvoice from ShippingOrder.
     */
    public function storeFromOrder(ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('create', PreInvoice::class);

        try {
            $preInvoice = $this->preInvoiceService->createFromShippingOrder($shippingOrder);
            return redirect()->route('pre-invoices.show', $preInvoice)
                ->with('success', 'Pre-factura generada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Issue a draft PreInvoice.
     */
    public function issue(PreInvoice $preInvoice): RedirectResponse
    {
        $this->authorize('update', $preInvoice);

        if ($preInvoice->status !== 'draft') {
            return back()->with('error', 'Solo se pueden emitir pre-facturas en estado borrador.');
        }

        $preInvoice->update([
            'status' => 'issued',
            'balance' => $preInvoice->total_amount,
        ]);

        return back()->with('success', 'Pre-factura emitida correctamente.');
    }

    /**
     * Cancel a PreInvoice.
     */
    public function cancel(PreInvoice $preInvoice): RedirectResponse
    {
        $this->authorize('update', $preInvoice);

        if ($preInvoice->status === 'cancelled') {
            return back()->with('error', 'Esta pre-factura ya está cancelada.');
        }

        if ($preInvoice->payments()->where('status', 'approved')->exists()) {
            return back()->with('error', 'No se puede cancelar una pre-factura con pagos aprobados. Anule los pagos primero.');
        }

        $preInvoice->update([
            'status' => 'cancelled',
            'balance' => 0,
        ]);

        return back()->with('success', 'Pre-factura cancelada correctamente.');
    }

    /**
     * Generate PDF for a PreInvoice.
     */
    public function print(PreInvoice $preInvoice)
    {
        $this->authorize('view', $preInvoice);
        return $this->pdfService->stream($preInvoice);
    }

    /**
     * Record a payment for a PreInvoice.
     */
    public function recordPayment(Request $request, PreInvoice $preInvoice): RedirectResponse
    {
        if (!$request->user()->can('billing.payments.create')) {
            abort(403, 'No tiene permiso para registrar pagos.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $preInvoice->balance],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            if ($request->user()->can('billing.payments.approve')) {
                $this->paymentService->quickPay($preInvoice, $validated, $request->user());
                return back()->with('success', 'Pago registrado y aprobado correctamente.');
            }

            $this->paymentService->recordPayment($preInvoice, $validated, $request->user());
            return back()->with('success', 'Pago registrado. Pendiente de aprobación.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve a pending payment.
     */
    public function approvePayment(Request $request, PreInvoice $preInvoice, Payment $payment): RedirectResponse
    {
        if (!$request->user()->can('billing.payments.approve')) {
            abort(403, 'No tiene permiso para aprobar pagos.');
        }

        try {
            $this->paymentService->approvePayment($payment, $request->user());
            return back()->with('success', 'Pago aprobado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Void a payment (requires higher permission).
     */
    public function voidPayment(Request $request, PreInvoice $preInvoice, Payment $payment): RedirectResponse
    {
        if (!$request->user()->can('billing.payments.void')) {
            abort(403, 'No tiene permiso para anular pagos.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->paymentService->voidPayment($payment, $request->user(), $validated['reason']);
            return back()->with('success', 'Pago anulado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export PreInvoices to CSV or JSON.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', PreInvoice::class);

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'format' => 'nullable|in:csv,json',
            'only_new' => 'nullable|boolean',
            'mark_exported' => 'nullable|boolean',
        ]);

        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'customer_id' => $request->input('customer_id'),
            'only_new' => $request->boolean('only_new', false),
        ];

        $invoices = $this->exportService->getExportableInvoices($filters);

        if ($invoices->isEmpty()) {
            if ($request->wantsJson() || $request->input('format') === 'json') {
                return response()->json([
                    'message' => 'No hay facturas para exportar con los filtros seleccionados.',
                    'data' => [],
                ], 200);
            }
            return back()->with('error', 'No hay facturas para exportar con los filtros seleccionados.');
        }

        $markExported = $request->boolean('mark_exported', true);
        $format = $request->input('format', 'csv');

        if ($format === 'json') {
            $data = $this->exportService->exportToJson($invoices, $markExported);
            return response()->json($data);
        }


        return $this->exportService->exportToCsv($invoices, $markExported);
    }

    /**
     * Generate a fiscal invoice from this pre-invoice.
     */
    public function generateInvoice(PreInvoice $preInvoice): RedirectResponse
    {
        $this->authorize('generateInvoice', $preInvoice);

        try {
            $invoice = $this->invoiceService->createFromPreInvoice(
                $preInvoice,
                request('ncf_type'),  // Optional override
                request('series')      // Optional override
            );

            return redirect()
                ->route('pre-invoices.show', $preInvoice)
                ->with('success', "Factura fiscal {$invoice->number} generada exitosamente con NCF: {$invoice->ncf}");
        } catch (\App\Exceptions\NoFiscalSequenceAvailableException $e) {
            return back()->with('error', "No hay rangos de NCF disponibles. {$e->getMessage()}");
        } catch (\App\Exceptions\FiscalSequenceExhaustedException $e) {
            return back()->with('error', "El rango de NCF se ha agotado. {$e->getMessage()}");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error generating invoice from pre-invoice', [
                'pre_invoice_id' => $preInvoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al generar la factura fiscal. Por favor contacte al administrador.');
        }
    }
}
