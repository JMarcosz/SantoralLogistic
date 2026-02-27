<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\WarehouseDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseDashboardController extends Controller
{
    public function __construct(
        private WarehouseDashboardService $dashboardService
    ) {}

    /**
     * Display the warehouse dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\InventoryItem::class);

        $filters = $request->only(['warehouse_id', 'date_from', 'date_to']);

        // Parse dates
        $dateFrom = !empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])
            : now()->subDays(30);
        $dateTo = !empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])
            : now();

        $warehouseId = !empty($filters['warehouse_id'])
            ? (int) $filters['warehouse_id']
            : null;

        // Get dashboard data
        $dashboardData = $this->dashboardService->getDashboardData(
            $warehouseId,
            $dateFrom,
            $dateTo
        );

        return Inertia::render('warehouse/dashboard/index', [
            'data' => $dashboardData,
            'filters' => [
                'warehouse_id' => $filters['warehouse_id'] ?? null,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
            ],
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}
