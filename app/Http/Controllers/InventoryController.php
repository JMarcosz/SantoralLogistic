<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    /**
     * Display inventory listing with filters.
     * Supports view=detail (default) or view=summary for SKU aggregation.
     */
    public function index(): Response
    {
        $filters = request()->only(['warehouse_id', 'customer_id', 'sku', 'location_code', 'view']);
        $view = $filters['view'] ?? 'detail';

        // Map 'sku' filter to 'item_code' for database query
        $itemCodeFilter = $filters['sku'] ?? null;

        // Base filters (shared between views)
        $baseFilters = array_filter([
            'warehouse_id' => $filters['warehouse_id'] ?? null,
            'customer_id' => $filters['customer_id'] ?? null,
            'item_code' => $itemCodeFilter,
            'location_code' => $filters['location_code'] ?? null,
        ]);

        if ($view === 'summary') {
            // Summary view: grouped by SKU
            $query = InventoryItem::selectRaw('
                    customer_id,
                    warehouse_id,
                    item_code,
                    description,
                    uom,
                    SUM(qty) as total_qty,
                    COUNT(*) as item_count
                ')
                ->with(['customer', 'warehouse'])
                ->where('qty', '>', 0)
                ->groupBy(['customer_id', 'warehouse_id', 'item_code', 'description', 'uom'])
                ->orderBy('customer_id')
                ->orderBy('item_code');

            // Apply filters
            if (!empty($baseFilters['warehouse_id'])) {
                $query->where('warehouse_id', $baseFilters['warehouse_id']);
            }
            if (!empty($baseFilters['customer_id'])) {
                $query->where('customer_id', $baseFilters['customer_id']);
            }
            if (!empty($baseFilters['item_code'])) {
                $query->where('item_code', 'like', "%{$baseFilters['item_code']}%");
            }

            $items = $query->paginate(20)->withQueryString();

            // Transform to add 'sku' alias for frontend compatibility
            $items->getCollection()->transform(function ($item) {
                $item->sku = $item->item_code;
                return $item;
            });
        } else {
            // Detail view: item by item
            $query = InventoryItem::with(['warehouse', 'customer', 'location', 'reservations'])
                ->where('qty', '>', 0)
                ->orderBy('created_at', 'desc');

            // Apply filters
            if (!empty($baseFilters['warehouse_id'])) {
                $query->where('warehouse_id', $baseFilters['warehouse_id']);
            }
            if (!empty($baseFilters['customer_id'])) {
                $query->where('customer_id', $baseFilters['customer_id']);
            }
            if (!empty($baseFilters['item_code'])) {
                $query->where('item_code', 'like', "%{$baseFilters['item_code']}%");
            }
            if (!empty($baseFilters['location_code'])) {
                $query->whereHas('location', function ($q) use ($baseFilters) {
                    $q->where('code', 'like', "%{$baseFilters['location_code']}%");
                });
            }

            $items = $query->paginate(20)->withQueryString();

            // Add computed available_qty and 'sku' alias for frontend compatibility
            $items->getCollection()->transform(function ($item) {
                $item->available_qty = $item->availableQuantity();
                $item->sku = $item->item_code;
                return $item;
            });
        }

        return Inertia::render('inventory/index', [
            'items' => $items,
            'filters' => $filters,
            'view' => $view,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name', 'code']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Display inventory grouped by customer.
     */
    public function byCustomer(): Response
    {
        $filters = request()->only(['warehouse_id', 'customer_id', 'item_code']);

        $query = InventoryItem::selectRaw('
                customer_id,
                warehouse_id,
                item_code,
                description,
                uom,
                SUM(qty) as total_qty,
                COUNT(*) as item_count
            ')
            ->with(['customer', 'warehouse'])
            ->where('qty', '>', 0)
            ->groupBy(['customer_id', 'warehouse_id', 'item_code', 'description', 'uom'])
            ->orderBy('customer_id')
            ->orderBy('item_code');

        // Apply filters
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['item_code'])) {
            $query->where('item_code', 'like', "%{$filters['item_code']}%");
        }

        $items = $query->paginate(20)->withQueryString();

        return Inertia::render('inventory/by-customer', [
            'items' => $items,
            'filters' => $filters,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name', 'code']),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Export inventory to Excel.
     */
    public function export()
    {
        $filters = request()->only(['warehouse_id', 'customer_id', 'item_code']);
        $filename = 'inventario_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new InventoryExport($filters), $filename);
    }

    /**
     * API for searching available inventory for reservation.
     */
    public function searchAvailable(): \Illuminate\Http\JsonResponse
    {
        $query = request('query');
        $customerId = request('customer_id');

        if (!$customerId) {
            return response()->json([]);
        }

        $items = InventoryItem::query()
            ->where('customer_id', $customerId)
            ->where('qty', '>', 0)
            ->where(function ($q) use ($query) {
                $q->where('item_code', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->withSum('reservations as reserved_qty_sum', 'qty_reserved')
            ->orderBy('item_code')
            ->limit(20)
            ->get();

        // Aggregate by Item Code
        $results = $items->groupBy('item_code')->map(function ($group) {
            $first = $group->first();
            $totalQty = $group->sum('qty');
            $totalReserved = $group->sum('reserved_qty_sum');
            $available = max(0, $totalQty - $totalReserved);

            if ($available <= 0) return null;

            return [
                'sku' => $first->item_code,
                'description' => $first->description ?? '',
                'available' => $available,
                'uom' => $first->uom,
            ];
        })->filter()->values();

        return response()->json($results);
    }
}
