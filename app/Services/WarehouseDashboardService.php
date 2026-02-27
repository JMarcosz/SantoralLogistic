<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\WarehouseReceiptStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\WarehouseReceipt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for warehouse dashboard KPIs and analytics.
 */
class WarehouseDashboardService
{
    /**
     * Get all dashboard data for a warehouse and date range.
     */
    public function getDashboardData(?int $warehouseId = null, ?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? now()->subDays(30);
        $dateTo = $dateTo ?? now();

        return [
            'kpis' => $this->getKpis($warehouseId, $dateFrom, $dateTo),
            'movementsByDay' => $this->getMovementsByDay($warehouseId, $dateFrom, $dateTo),
            'movementsByType' => $this->getMovementsByType($warehouseId, $dateFrom, $dateTo),
            'topClients' => $this->getTopClients($warehouseId, 5),
            'receiptsByStatus' => $this->getReceiptsByStatus($warehouseId, $dateFrom, $dateTo),
            'recentReceipts' => $this->getRecentReceipts($warehouseId, 5),
            'recentMovements' => $this->getRecentMovements($warehouseId, 5),
        ];
    }

    /**
     * Get main KPIs.
     */
    public function getKpis(?int $warehouseId, Carbon $dateFrom, Carbon $dateTo): array
    {
        // Total inventory items with stock
        $inventoryQuery = InventoryItem::where('qty', '>', 0);
        if ($warehouseId) {
            $inventoryQuery->where('warehouse_id', $warehouseId);
        }
        $totalItems = $inventoryQuery->count();
        $totalQty = (float) $inventoryQuery->sum('qty');
        $totalSkus = $inventoryQuery->distinct('item_code')->count('item_code');

        // Receipts in period
        $receiptsQuery = WarehouseReceipt::whereBetween('created_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()]);
        if ($warehouseId) {
            $receiptsQuery->where('warehouse_id', $warehouseId);
        }
        $totalReceipts = $receiptsQuery->count();
        $receivedReceipts = (clone $receiptsQuery)->where('status', WarehouseReceiptStatus::Received)->count();

        // Movements in period
        $movementsQuery = InventoryMovement::whereBetween('created_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()]);
        if ($warehouseId) {
            $movementsQuery->whereHas('inventoryItem', fn($q) => $q->where('warehouse_id', $warehouseId));
        }
        $totalMovements = $movementsQuery->count();

        // Inbound movements (receive, return)
        $inboundQty = (float) (clone $movementsQuery)
            ->whereIn('movement_type', [MovementType::Receive->value, MovementType::Return->value])
            ->where('qty', '>', 0)
            ->sum('qty');

        // Outbound movements (pick)
        $outboundQty = (float) abs((clone $movementsQuery)
            ->where('movement_type', MovementType::Pick->value)
            ->sum('qty'));

        // Adjustments
        $adjustments = (clone $movementsQuery)
            ->where('movement_type', MovementType::Adjust->value)
            ->count();

        // Unique clients with stock
        $clientsQuery = InventoryItem::where('qty', '>', 0);
        if ($warehouseId) {
            $clientsQuery->where('warehouse_id', $warehouseId);
        }
        $totalClients = $clientsQuery->distinct('customer_id')->count('customer_id');

        return [
            'total_items' => $totalItems,
            'total_qty' => $totalQty,
            'total_skus' => $totalSkus,
            'total_clients' => $totalClients,
            'total_receipts' => $totalReceipts,
            'received_receipts' => $receivedReceipts,
            'total_movements' => $totalMovements,
            'inbound_qty' => $inboundQty,
            'outbound_qty' => $outboundQty,
            'adjustments' => $adjustments,
            'period' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get movements grouped by day for chart.
     */
    public function getMovementsByDay(?int $warehouseId, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $query = InventoryMovement::query()
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN movement_type IN ('receive', 'return') AND qty > 0 THEN qty ELSE 0 END) as inbound"),
                DB::raw("SUM(CASE WHEN movement_type = 'pick' THEN ABS(qty) ELSE 0 END) as outbound"),
                DB::raw("SUM(CASE WHEN movement_type = 'adjust' THEN 1 ELSE 0 END) as adjustments")
            )
            ->whereBetween('created_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date');

        if ($warehouseId) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('warehouse_id', $warehouseId));
        }

        return $query->get()->map(fn($row) => [
            'date' => Carbon::parse($row->date)->format('M d'),
            'inbound' => (float) $row->inbound,
            'outbound' => (float) $row->outbound,
            'adjustments' => (int) $row->adjustments,
        ]);
    }

    /**
     * Get movements grouped by type.
     */
    public function getMovementsByType(?int $warehouseId, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $query = InventoryMovement::query()
            ->select('movement_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(ABS(qty)) as qty'))
            ->whereBetween('created_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->groupBy('movement_type');

        if ($warehouseId) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('warehouse_id', $warehouseId));
        }

        return $query->get()->map(fn($row) => [
            'type' => $row->movement_type->value ?? $row->movement_type,
            'label' => $row->movement_type instanceof MovementType
                ? $row->movement_type->label()
                : ucfirst($row->movement_type),
            'count' => (int) $row->count,
            'qty' => (float) $row->qty,
        ]);
    }

    /**
     * Get top clients by inventory quantity.
     */
    public function getTopClients(?int $warehouseId, int $limit = 5): Collection
    {
        $query = InventoryItem::query()
            ->select('customer_id', DB::raw('SUM(qty) as total_qty'), DB::raw('COUNT(DISTINCT item_code) as sku_count'))
            ->where('qty', '>', 0)
            ->groupBy('customer_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->with('customer');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->map(fn($row) => [
            'customer_id' => $row->customer_id,
            'name' => $row->customer?->name ?? 'Sin cliente',
            'total_qty' => (float) $row->total_qty,
            'sku_count' => (int) $row->sku_count,
        ]);
    }

    /**
     * Get receipts grouped by status.
     */
    public function getReceiptsByStatus(?int $warehouseId, Carbon $dateFrom, Carbon $dateTo): Collection
    {
        $query = WarehouseReceipt::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->groupBy('status');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->map(fn($row) => [
            'status' => $row->status->value ?? $row->status,
            'label' => $row->status instanceof WarehouseReceiptStatus
                ? $row->status->label()
                : ucfirst($row->status),
            'color' => $row->status instanceof WarehouseReceiptStatus
                ? $row->status->color()
                : 'gray',
            'count' => (int) $row->count,
        ]);
    }

    /**
     * Get recent warehouse receipts.
     */
    public function getRecentReceipts(?int $warehouseId, int $limit = 5): Collection
    {
        $query = WarehouseReceipt::query()
            ->with(['warehouse', 'customer'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()->map(fn($receipt) => [
            'id' => $receipt->id,
            'receipt_number' => $receipt->receipt_number,
            'warehouse' => $receipt->warehouse?->name,
            'customer' => $receipt->customer?->name,
            'status' => $receipt->status->value,
            'status_label' => $receipt->status->label(),
            'status_color' => $receipt->status->color(),
            'created_at' => $receipt->created_at->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Get recent inventory movements.
     */
    public function getRecentMovements(?int $warehouseId, int $limit = 5): Collection
    {
        $query = InventoryMovement::query()
            ->with(['inventoryItem.warehouse', 'inventoryItem.customer', 'user'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($warehouseId) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('warehouse_id', $warehouseId));
        }

        return $query->get()->map(fn($mov) => [
            'id' => $mov->id,
            'type' => $mov->movement_type->value,
            'type_label' => $mov->movement_type->label(),
            'sku' => $mov->inventoryItem?->item_code,
            'qty' => $mov->qty,
            'user' => $mov->user?->name,
            'created_at' => $mov->created_at->format('Y-m-d H:i'),
        ]);
    }
}
