<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\CycleCount;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Service for generating warehouse operational reports.
 *
 * Provides consolidated queries for inventory snapshots,
 * movement history, and adjustment KPIs.
 */
class WarehouseReportService
{
    /**
     * Get paginated inventory report with optional filters.
     *
     * @param array $filters warehouse_id, customer_id, sku, location_id
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInventoryReport(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = InventoryItem::query()
            ->with(['warehouse', 'customer', 'location'])
            ->where('qty', '>', 0);

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['item_code'])) {
            $query->where('item_code', 'like', "%{$filters['item_code']}%");
        }
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        return $query->orderBy('warehouse_id')
            ->orderBy('item_code')
            ->paginate($perPage)
            ->through(fn($item) => [
                'id' => $item->id,
                'warehouse' => $item->warehouse?->name,
                'warehouse_code' => $item->warehouse?->code,
                'customer' => $item->customer?->name,
                'location' => $item->location?->code,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'qty' => $item->qty,
                'available_qty' => $item->availableQuantity(),
                'reserved_qty' => $item->reservedQuantity(),
                'uom' => $item->uom,
                'lot_number' => $item->lot_number,
                'serial_number' => $item->serial_number,
                'expiration_date' => $item->expiration_date?->format('Y-m-d'),
            ]);
    }

    /**
     * Get paginated movements report with optional filters.
     *
     * @param array $filters date_from, date_to, warehouse_id, customer_id, item_code, type
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getMovementsReport(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = InventoryMovement::query()
            ->with(['inventoryItem.warehouse', 'inventoryItem.customer', 'fromLocation', 'toLocation', 'user']);

        // Date range
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        // Warehouse filter (through inventory item)
        if (!empty($filters['warehouse_id'])) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('warehouse_id', $filters['warehouse_id']));
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('customer_id', $filters['customer_id']));
        }

        // Item code filter
        if (!empty($filters['item_code'])) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('item_code', 'like', "%{$filters['item_code']}%"));
        }

        // Movement type filter
        if (!empty($filters['type'])) {
            $query->where('movement_type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn($movement) => [
                'id' => $movement->id,
                'date' => $movement->created_at->format('Y-m-d H:i'),
                'type' => $movement->movement_type->value,
                'type_label' => $movement->movement_type->label(),
                'warehouse' => $movement->inventoryItem?->warehouse?->name,
                'customer' => $movement->inventoryItem?->customer?->name,
                'item_code' => $movement->inventoryItem?->item_code,
                'description' => $movement->inventoryItem?->description,
                'from_location' => $movement->fromLocation?->code,
                'to_location' => $movement->toLocation?->code,
                'qty' => $movement->qty,
                'reference' => $movement->reference,
                'notes' => $movement->notes,
                'user' => $movement->user?->name,
            ]);
    }

    /**
     * Get adjustment KPIs for a date range.
     *
     * @param Carbon|null $dateFrom
     * @param Carbon|null $dateTo
     * @return array
     */
    public function getAdjustmentKpis(?Carbon $dateFrom = null, ?Carbon $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? now()->subDays(30);
        $dateTo = $dateTo ?? now();

        // Total adjustments query
        $adjustmentsQuery = InventoryMovement::query()
            ->where('movement_type', MovementType::Adjust)
            ->whereBetween('created_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()]);

        $totalAdjustments = $adjustmentsQuery->count();
        $totalAdjustmentQty = (float) $adjustmentsQuery->selectRaw('SUM(ABS(qty)) as total')->value('total') ?? 0;

        // Cycle count adjustments
        $cycleCountAdjustments = InventoryMovement::query()
            ->where('movement_type', MovementType::Adjust)
            ->where('reference', 'like', '%cycle_count%')
            ->whereBetween('created_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->count();

        // Cycle counts completed in period
        $cycleCountsCompleted = CycleCount::query()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->count();

        // Lines with differences in completed cycle counts
        $totalCycleCountLines = 0;
        $linesWithDifferences = 0;

        $completedCounts = CycleCount::query()
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->withCount(['lines', 'lines as lines_with_diff_count' => fn($q) => $q->where('difference_qty', '!=', 0)])
            ->get();

        foreach ($completedCounts as $count) {
            $totalCycleCountLines += $count->lines_count;
            $linesWithDifferences += $count->lines_with_diff_count;
        }

        $accuracyRate = $totalCycleCountLines > 0
            ? round((1 - ($linesWithDifferences / $totalCycleCountLines)) * 100, 2)
            : 100;

        return [
            'period' => [
                'from' => $dateFrom->format('Y-m-d'),
                'to' => $dateTo->format('Y-m-d'),
            ],
            'total_adjustments' => $totalAdjustments,
            'total_adjustment_qty' => $totalAdjustmentQty,
            'cycle_count_adjustments' => $cycleCountAdjustments,
            'other_adjustments' => $totalAdjustments - $cycleCountAdjustments,
            'cycle_counts_completed' => $cycleCountsCompleted,
            'cycle_count_lines_total' => $totalCycleCountLines,
            'cycle_count_lines_with_diff' => $linesWithDifferences,
            'inventory_accuracy_rate' => $accuracyRate,
        ];
    }
}
