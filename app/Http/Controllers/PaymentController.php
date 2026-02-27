<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Events\PaymentPosted;
use App\Events\PaymentVoided;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);
        $payments = Payment::query()
            ->with(['customer', 'paymentMethod', 'creator'])
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->customer_id, function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($request->from_date, function ($query, $fromDate) {
                $query->whereDate('payment_date', '>=', $fromDate);
            })
            ->when($request->to_date, function ($query, $toDate) {
                $query->whereDate('payment_date', '<=', $toDate);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('payments/Index', [
            'payments' => $payments,
            'customers' => Customer::select('id', 'name')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::active()->ordered()->get(),
            'filters' => $request->only(['type', 'status', 'customer_id', 'from_date', 'to_date', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Payment::class);
        $type = $request->get('type', 'inbound');
        $customerId = $request->get('customer_id');

        // Get pending invoices for the customer if specified
        $pendingInvoices = [];
        if ($customerId) {
            $pendingInvoices = Invoice::where('customer_id', $customerId)
                ->where('status', 'issued')
                // Only invoices with remaining balance (using stored balance field)
                ->where('balance', '>', 0)
                ->orderBy('issue_date')
                ->get();
        }

        return Inertia::render('payments/Create', [
            'type' => $type,
            'customers' => Customer::select('id', 'name', 'fiscal_name')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::active()->ordered()->get(),
            'pendingInvoices' => $pendingInvoices,
            'selectedCustomerId' => $customerId ? (int) $customerId : null,
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $validated = $request->validate([
            'type' => ['required', 'in:inbound,outbound'],
            'customer_id' => ['required_if:type,inbound', 'nullable', 'exists:customers,id'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'isr_withholding_amount' => ['nullable', 'numeric', 'min:0'],
            'itbis_withholding_amount' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'exists:invoices,id'],
            'allocations.*.amount_applied' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ]);

        // Validate that sum of allocations doesn't exceed payment amount
        $totalAllocated = collect($validated['allocations'] ?? [])->sum('amount_applied');
        if ($totalAllocated > $validated['amount']) {
            return back()
                ->withInput()
                ->with('error', 'El total asignado a facturas no puede exceder el monto del pago.');
        }

        // Validate withholdings don't exceed amount
        $totalWithholdings = ($validated['isr_withholding_amount'] ?? 0) + ($validated['itbis_withholding_amount'] ?? 0);
        if ($totalWithholdings > $validated['amount']) {
            return back()
                ->withInput()
                ->with('error', 'Las retenciones no pueden exceder el monto del pago.');
        }

        try {
            $payment = $this->paymentService->store($validated, Auth::user());

            return redirect()
                ->route('payments.show', $payment)
                ->with('success', 'Pago creado exitosamente.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): Response
    {
        $this->authorize('view', $payment);
        $payment->load([
            'customer',
            'paymentMethod',
            'allocations.invoice',
            'creator',
            'postedBy',
            'voider',
        ]);

        return Inertia::render('payments/Show', [
            'payment' => $payment,
            'can' => [
                'edit' => $payment->canEdit(),
                'post' => $payment->canPost(),
                'void' => $payment->canVoid(),
                'delete' => $payment->canDelete(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit(Payment $payment): Response
    {
        $this->authorize('update', $payment);
        if (!$payment->canEdit()) {
            abort(403, 'Este pago no puede ser editado.');
        }

        $payment->load(['allocations.invoice', 'customer', 'paymentMethod']);

        // Get pending invoices for this customer
        $pendingInvoices = [];
        if ($payment->customer_id) {
            $pendingInvoices = Invoice::where('customer_id', $payment->customer_id)
                ->where('status', 'issued')
                ->orderBy('issue_date')
                ->get();
        }

        return Inertia::render('payments/Create', [
            'payment' => $payment,
            'customers' => Customer::select('id', 'name', 'fiscal_name')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::active()->ordered()->get(),
            'pendingInvoices' => $pendingInvoices,
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);
        if (!$payment->canEdit()) {
            return back()->with('error', 'Este pago no puede ser editado.');
        }

        $validated = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required_with:allocations', 'exists:invoices,id'],
            'allocations.*.amount_applied' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            // Recalculate derived fields
            $isrWithholding = $validated['isr_withholding_amount'] ?? 0;
            $itbisWithholding = $validated['itbis_withholding_amount'] ?? 0;
            $exchangeRate = $validated['exchange_rate'] ?? 1;

            $payment->update([
                'payment_method_id' => $validated['payment_method_id'],
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'currency_code' => $validated['currency_code'],
                'exchange_rate' => $exchangeRate,
                'base_amount' => $validated['amount'] * $exchangeRate,
                'isr_withholding_amount' => $isrWithholding,
                'itbis_withholding_amount' => $itbisWithholding,
                'net_amount' => $validated['amount'] - $isrWithholding - $itbisWithholding,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update allocations
            $payment->allocations()->delete();
            if (!empty($validated['allocations'])) {
                foreach ($validated['allocations'] as $allocation) {
                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $allocation['invoice_id'],
                        'amount_applied' => $allocation['amount_applied'],
                    ]);
                }
            }
            $payment->recalculateAllocations();
        });

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Pago actualizado exitosamente.');
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);
        if (!$payment->canDelete()) {
            return back()->with('error', 'Este pago no puede ser eliminado.');
        }

        // Recalculate invoice balances before deleting
        $this->paymentService->recalculateInvoiceBalances($payment);

        $payment->delete();

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pago eliminado exitosamente.');
    }

    /**
     * Post the payment.
     */
    public function post(Payment $payment): RedirectResponse
    {
        $this->authorize('post', $payment);
        if (!$payment->canPost()) {
            return back()->with('error', 'Este pago no puede ser contabilizado.');
        }

        try {
            DB::transaction(function () use ($payment) {
                $payment->update([
                    'status' => 'posted',
                    'posted_by' => Auth::id(),
                    'posted_at' => now(),
                ]);

                // Dispatch event to create journal entry (handled by listener)
                PaymentPosted::dispatch($payment);
            });

            return back()->with('success', 'Pago contabilizado exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al contabilizar: ' . $e->getMessage());
        }
    }

    /**
     * Void the payment.
     */
    public function void(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('void', $payment);
        if (!$payment->canVoid()) {
            return back()->with('error', 'Este pago no puede ser anulado.');
        }

        $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            DB::transaction(function () use ($payment, $request) {
                $payment->update([
                    'status' => 'voided',
                    'voided_by' => Auth::id(),
                    'voided_at' => now(),
                    'void_reason' => $request->void_reason,
                ]);

                // Recalculate invoice balances for all allocations
                // This will restore the balance since voided payments are excluded
                $this->paymentService->recalculateInvoiceBalances($payment);

                // Dispatch event to reverse journal entry (handled by listener)
                PaymentVoided::dispatch($payment);
            });

            return back()->with('success', 'Pago anulado exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    /**
     * Get pending invoices for a customer (AJAX).
     */
    public function pendingInvoices(Customer $customer)
    {
        // Use stored balance field for better performance
        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('status', 'issued')
            ->where('balance', '>', 0)
            ->orderBy('issue_date')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'ncf' => $invoice->ncf,
                    'issue_date' => $invoice->issue_date,
                    'total_amount' => $invoice->total_amount,
                    'amount_paid' => $invoice->amount_paid,
                    'balance' => $invoice->balance,
                    'currency_code' => $invoice->currency_code,
                ];
            });

        return response()->json($invoices);
    }

    /**
     * PDF Receipt Generator.
     */

    public function pdf(Payment $payment)
    {
        $this->authorize('view', $payment);

        $pdfService = app(\App\Services\PaymentPdfService::class);

        return $pdfService->download($payment);
    }
}
