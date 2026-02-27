<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Exports\MovementsExport;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Services\WarehouseReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseReportController extends Controller
{
    public function __construct(
        private WarehouseReportService $reportService
    ) {}

    /**
     * Display the inventory report.
     */
    public function inventory(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\InventoryItem::class);

        $filters = $request->only(['warehouse_id', 'customer_id', 'item_code', 'location_id']);
        $inventory = $this->reportService->getInventoryReport($filters, 25);

        return Inertia::render('warehouse/reports/inventory', [
            'inventory' => $inventory,
            'filters' => $filters,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name', 'code']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Display the movements report.
     */
    public function movements(Request $request): Response
    {
        $this->authorize('viewAny', \App\Models\InventoryMovement::class);

        $filters = $request->only(['date_from', 'date_to', 'warehouse_id', 'customer_id', 'item_code', 'type']);

        // Default date range: last 30 days
        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_from'] = now()->subDays(30)->format('Y-m-d');
            $filters['date_to'] = now()->format('Y-m-d');
        }

        $movements = $this->reportService->getMovementsReport($filters, 25);

        // Get KPIs for the selected period
        $kpis = $this->reportService->getAdjustmentKpis(
            !empty($filters['date_from']) ? Carbon::parse($filters['date_from']) : null,
            !empty($filters['date_to']) ? Carbon::parse($filters['date_to']) : null
        );

        return Inertia::render('warehouse/reports/movements', [
            'movements' => $movements,
            'filters' => $filters,
            'kpis' => $kpis,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name', 'code']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'movementTypes' => [
                ['value' => 'receive', 'label' => 'Recepción'],
                ['value' => 'putaway', 'label' => 'Ubicación'],
                ['value' => 'pick', 'label' => 'Picking'],
                ['value' => 'transfer', 'label' => 'Transferencia'],
                ['value' => 'adjust', 'label' => 'Ajuste'],
                ['value' => 'return', 'label' => 'Devolución'],
            ],
        ]);
    }

    /**
     * Export inventory to Excel.
     */
    public function exportInventory(Request $request)
    {
        $this->authorize('viewAny', \App\Models\InventoryItem::class);

        $filters = $request->only(['warehouse_id', 'customer_id', 'item_code', 'location_id']);

        return Excel::download(new InventoryExport($filters), 'inventario-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export movements to Excel.
     */
    public function exportMovements(Request $request)
    {
        $this->authorize('viewAny', \App\Models\InventoryMovement::class);

        $filters = $request->only(['date_from', 'date_to', 'warehouse_id', 'customer_id', 'item_code', 'type']);

        return Excel::download(new MovementsExport($filters), 'movimientos-' . now()->format('Y-m-d') . '.xlsx');
    }
}
