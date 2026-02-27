<?php

namespace App\Http\Controllers;

use App\Services\AccountsReceivableService;
use App\Models\Customer;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AccountsReceivableController extends Controller
{
    public function __construct(
        protected AccountsReceivableService $arService
    ) {}

    /**
     * Display the Accounts Receivable dashboard.
     */
    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', \App\Models\PreInvoice::class);

        // Get filters
        $filters = $request->only([
            'customer_id',
            'currency_code',
            'aging_bucket',
            'date_from',
            'date_to',
            'order_by',
            'order_dir',
        ]);

        // Get aging summary by currency
        $agingSummary = $this->arService->getAgingSummaryByCurrency(
            $filters['customer_id'] ?? null
        );

        // Get open invoices
        $invoices = $this->arService->getOpenInvoices($filters);

        // Get dashboard KPIs
        $kpis = $this->arService->getDashboardKpis();

        // Get customers for filter
        $customers = Customer::select('id', 'name', 'code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get currencies for filter
        $currencies = Currency::select('id', 'code', 'symbol', 'name')
            ->orderBy('code')
            ->get();

        return Inertia::render('billing/accounts-receivable/index', [
            'invoices' => $invoices,
            'agingSummary' => $agingSummary,
            'kpis' => $kpis,
            'customers' => $customers,
            'currencies' => $currencies,
            'filters' => $filters,
            'can' => [
                'recordPayment' => $request->user()->can('billing.payments.create'),
                'approvePayment' => $request->user()->can('billing.payments.approve'),
                'voidPayment' => $request->user()->can('billing.payments.void'),
                'export' => $request->user()->can('billing.ar.export'),
            ],
        ]);
    }

    /**
     * Export accounts receivable to CSV.
     */
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\PreInvoice::class);

        $filters = $request->only(['customer_id', 'currency_code']);
        $data = $this->arService->getExportData($filters);

        $headers = [
            'Número',
            'Cliente',
            'Código Cliente',
            'Moneda',
            'Fecha Emisión',
            'Fecha Vencimiento',
            'Días Vencido',
            'Aging',
            'Total',
            'Pagado',
            'Saldo',
        ];

        $csv = implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', [
                $row['number'],
                '"' . str_replace('"', '""', $row['customer_name']) . '"',
                $row['customer_code'],
                $row['currency_code'],
                $row['issue_date'],
                $row['due_date'],
                $row['days_overdue'],
                '"' . $row['aging_bucket'] . '"',
                $row['total_amount'],
                $row['paid_amount'],
                $row['balance'],
            ]) . "\n";
        }

        $filename = 'cuentas_por_cobrar_' . now()->format('Y-m-d_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
