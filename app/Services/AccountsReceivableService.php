<?php

namespace App\Services;

use App\Models\PreInvoice;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountsReceivableService
{
    /**
     * Get open (unpaid/partially paid) pre-invoices.
     */
    public function getOpenInvoices(array $filters = []): LengthAwarePaginator
    {
        $query = PreInvoice::with(['customer', 'shippingOrder'])
            ->where('status', PreInvoice::STATUS_ISSUED)
            ->where('balance', '>', 0);

        // Filter by customer
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Filter by currency
        if (!empty($filters['currency_code'])) {
            $query->where('currency_code', $filters['currency_code']);
        }

        // Filter by aging bucket
        if (!empty($filters['aging_bucket'])) {
            $this->applyAgingBucketFilter($query, $filters['aging_bucket']);
        }

        // Filter by date range (issue date)
        if (!empty($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }

        // Ordering
        $orderBy = $filters['order_by'] ?? 'due_date';
        $orderDir = $filters['order_dir'] ?? 'asc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Get aging summary grouped by currency.
     */
    public function getAgingSummaryByCurrency(?int $customerId = null): array
    {
        $query = PreInvoice::query()
            ->where('status', PreInvoice::STATUS_ISSUED)
            ->where('balance', '>', 0);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $invoices = $query->get();

        $summary = [];

        foreach ($invoices as $invoice) {
            $currency = $invoice->currency_code;
            $bucket = $invoice->aging_bucket;
            $balance = (float) $invoice->balance;

            if (!isset($summary[$currency])) {
                $summary[$currency] = [
                    'current' => ['count' => 0, 'total' => 0],
                    '1_30' => ['count' => 0, 'total' => 0],
                    '31_60' => ['count' => 0, 'total' => 0],
                    '61_90' => ['count' => 0, 'total' => 0],
                    'over_90' => ['count' => 0, 'total' => 0],
                    'grand_total' => 0,
                    'total_count' => 0,
                ];
            }

            $summary[$currency][$bucket]['count']++;
            $summary[$currency][$bucket]['total'] += $balance;
            $summary[$currency]['grand_total'] += $balance;
            $summary[$currency]['total_count']++;
        }

        return $summary;
    }

    /**
     * Get customer balances.
     */
    public function getCustomerBalances(?string $currencyCode = null): Collection
    {
        $query = PreInvoice::with('customer')
            ->select('customer_id', 'currency_code')
            ->selectRaw('SUM(balance) as total_balance')
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('SUM(CASE WHEN due_date < CURDATE() THEN balance ELSE 0 END) as overdue_balance')
            ->where('status', PreInvoice::STATUS_ISSUED)
            ->where('balance', '>', 0)
            ->groupBy('customer_id', 'currency_code');

        if ($currencyCode) {
            $query->where('currency_code', $currencyCode);
        }

        return $query->orderByDesc('total_balance')->get();
    }

    /**
     * Get dashboard KPIs.
     */
    public function getDashboardKpis(): array
    {
        $openInvoices = PreInvoice::where('status', PreInvoice::STATUS_ISSUED)
            ->where('balance', '>', 0)
            ->get();

        // Group by currency
        $byCurrency = [];

        foreach ($openInvoices as $invoice) {
            $currency = $invoice->currency_code;

            if (!isset($byCurrency[$currency])) {
                $byCurrency[$currency] = [
                    'total_receivable' => 0,
                    'overdue_amount' => 0,
                    'current_amount' => 0,
                    'invoice_count' => 0,
                    'overdue_count' => 0,
                ];
            }

            $balance = (float) $invoice->balance;
            $byCurrency[$currency]['total_receivable'] += $balance;
            $byCurrency[$currency]['invoice_count']++;

            if ($invoice->isOverdue()) {
                $byCurrency[$currency]['overdue_amount'] += $balance;
                $byCurrency[$currency]['overdue_count']++;
            } else {
                $byCurrency[$currency]['current_amount'] += $balance;
            }
        }

        return $byCurrency;
    }

    /**
     * Export accounts receivable data.
     */
    public function getExportData(array $filters = []): Collection
    {
        $query = PreInvoice::with(['customer'])
            ->where('status', PreInvoice::STATUS_ISSUED)
            ->where('balance', '>', 0);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['currency_code'])) {
            $query->where('currency_code', $filters['currency_code']);
        }

        return $query->orderBy('due_date')->get()->map(function ($invoice) {
            return [
                'number' => $invoice->number,
                'customer_name' => $invoice->customer->name ?? 'N/A',
                'customer_code' => $invoice->customer->code ?? 'N/A',
                'currency_code' => $invoice->currency_code,
                'issue_date' => $invoice->issue_date->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d') ?? 'N/A',
                'days_overdue' => $invoice->days_overdue,
                'aging_bucket' => $this->getBucketLabel($invoice->aging_bucket),
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'balance' => $invoice->balance,
            ];
        });
    }

    /**
     * Apply aging bucket filter to query.
     */
    protected function applyAgingBucketFilter($query, string $bucket): void
    {
        $today = now()->startOfDay();

        switch ($bucket) {
            case 'current':
                $query->where(function ($q) use ($today) {
                    $q->whereNull('due_date')
                        ->orWhere('due_date', '>=', $today);
                });
                break;
            case '1_30':
                $query->where('due_date', '<', $today)
                    ->where('due_date', '>=', $today->copy()->subDays(30));
                break;
            case '31_60':
                $query->where('due_date', '<', $today->copy()->subDays(30))
                    ->where('due_date', '>=', $today->copy()->subDays(60));
                break;
            case '61_90':
                $query->where('due_date', '<', $today->copy()->subDays(60))
                    ->where('due_date', '>=', $today->copy()->subDays(90));
                break;
            case 'over_90':
                $query->where('due_date', '<', $today->copy()->subDays(90));
                break;
        }
    }

    /**
     * Get bucket label in Spanish.
     */
    protected function getBucketLabel(string $bucket): string
    {
        return match ($bucket) {
            'current' => 'Al día',
            '1_30' => '1-30 días',
            '31_60' => '31-60 días',
            '61_90' => '61-90 días',
            'over_90' => '+90 días',
            default => $bucket,
        };
    }
}
