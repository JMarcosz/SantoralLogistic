<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceAnalyticsController extends Controller
{
    /**
     * Display invoice analytics dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('analytics', Invoice::class);

        // Default to current month
        $startDate = $request->from_date ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->to_date ?? now()->endOfMonth()->format('Y-m-d');

        // Total invoices
        $totalInvoices = Invoice::whereBetween('issue_date', [$startDate, $endDate])->count();

        // Total revenue (only issued invoices)
        $totalRevenue = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', 'issued')
            ->sum('total_amount');

        // Cancelled count
        $cancelledCount = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', 'cancelled')
            ->count();

        // Average invoice value
        $averageValue = $totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0;

        // By status
        $byStatus = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('status')
            ->get();

        // By customer (top 10)
        $byCustomer = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->select('customer_id', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->with('customer:id,name,fiscal_name')
            ->get()
            ->map(function ($item) {
                return [
                    'customer_name' => $item->customer->fiscal_name ?? $item->customer->name,
                    'count' => $item->count,
                    'total' => $item->total,
                ];
            });

        // By NCF type
        $byNcfType = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->select('ncf_type', DB::raw('count(*) as count'))
            ->groupBy('ncf_type')
            ->get();

        // Daily revenue (for chart)
        $dailyRevenue = Invoice::whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', 'issued')
            ->select(DB::raw('DATE(issue_date) as date'), DB::raw('sum(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Billing/Invoices/Analytics', [
            'metrics' => [
                'total_invoices' => $totalInvoices,
                'total_revenue' => $totalRevenue,
                'cancelled_count' => $cancelledCount,
                'average_value' => $averageValue,
            ],
            'charts' => [
                'by_status' => $byStatus,
                'by_customer' => $byCustomer,
                'by_ncf_type' => $byNcfType,
                'daily_revenue' => $dailyRevenue,
            ],
            'filters' => [
                'from_date' => $startDate,
                'to_date' => $endDate,
            ],
        ]);
    }
}
