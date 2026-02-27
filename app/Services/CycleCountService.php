<?php

namespace App\Services;

use App\Enums\CycleCountStatus;
use App\Models\CycleCount;
use App\Models\CycleCountLine;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Cycle Counts (inventory verification).
 *
 * Handles creation, counting, and reconciliation of cycle counts
 * with automatic inventory adjustment through movements.
 */
class CycleCountService
{
    public function __construct(
        private InventoryMovementService $movementService
    ) {}

    /**
     * Create a new cycle count for a warehouse.
     *
     * @param Warehouse $warehouse The warehouse to count
     * @param array $filters Optional filters: customer_id, sku, location_id
     * @param string|null $reference Optional reference
     * @param \DateTimeInterface|null $scheduledAt Optional scheduled date
     * @param string|null $notes Optional notes
     * @return CycleCount
     */
    public function create(
        Warehouse $warehouse,
        array $filters = [],
        ?string $reference = null,
        ?\DateTimeInterface $scheduledAt = null,
        ?string $notes = null
    ): CycleCount {
        return DB::transaction(function () use ($warehouse, $filters, $reference, $scheduledAt, $notes) {
            // Create the cycle count
            $cycleCount = CycleCount::create([
                'warehouse_id' => $warehouse->id,
                'status' => CycleCountStatus::Draft,
                'reference' => $reference,
                'scheduled_at' => $scheduledAt,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            // Build query for inventory items
            $query = InventoryItem::where('warehouse_id', $warehouse->id)
                ->where('qty', '>', 0);

            // Apply filters
            if (!empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }
            if (!empty($filters['sku'])) {
                $query->where('sku', 'like', "%{$filters['sku']}%");
            }
            if (!empty($filters['location_id'])) {
                $query->where('location_id', $filters['location_id']);
            }

            // Get items and create lines
            $items = $query->get();

            foreach ($items as $item) {
                CycleCountLine::create([
                    'cycle_count_id' => $cycleCount->id,
                    'inventory_item_id' => $item->id,
                    'expected_qty' => $item->qty,
                    'counted_qty' => null,
                    'difference_qty' => null,
                    'counted_at' => null,
                ]);
            }

            Log::info('Cycle count created', [
                'cycle_count_id' => $cycleCount->id,
                'warehouse_id' => $warehouse->id,
                'lines_count' => $items->count(),
            ]);

            return $cycleCount;
        });
    }

    /**
     * Start the counting process.
     *
     * @param CycleCount $cycleCount
     * @return CycleCount
     * @throws \InvalidArgumentException
     */
    public function start(CycleCount $cycleCount): CycleCount
    {
        if (!$cycleCount->canStart()) {
            throw new \InvalidArgumentException(
                "No se puede iniciar un conteo en estado '{$cycleCount->status->label()}'."
            );
        }

        if ($cycleCount->totalLines() === 0) {
            throw new \InvalidArgumentException(
                'No hay líneas para contar. Verifique los filtros y el inventario.'
            );
        }

        $cycleCount->update(['status' => CycleCountStatus::InProgress]);

        Log::info('Cycle count started', [
            'cycle_count_id' => $cycleCount->id,
        ]);

        return $cycleCount;
    }

    /**
     * Update a line's counted quantity.
     *
     * @param CycleCountLine $line
     * @param float $countedQty
     * @return CycleCountLine
     * @throws \InvalidArgumentException
     */
    public function updateLine(CycleCountLine $line, float $countedQty): CycleCountLine
    {
        $cycleCount = $line->cycleCount;

        if (!$cycleCount->isInProgress()) {
            throw new \InvalidArgumentException(
                'Solo se puede registrar conteo cuando el ciclo está en progreso.'
            );
        }

        if ($countedQty < 0) {
            throw new \InvalidArgumentException('La cantidad contada no puede ser negativa.');
        }

        $difference = $countedQty - $line->expected_qty;

        $line->update([
            'counted_qty' => $countedQty,
            'difference_qty' => $difference,
            'counted_at' => now(),
        ]);

        Log::info('Cycle count line updated', [
            'line_id' => $line->id,
            'expected_qty' => $line->expected_qty,
            'counted_qty' => $countedQty,
            'difference_qty' => $difference,
        ]);

        return $line;
    }

    /**
     * Complete the cycle count and reconcile differences.
     *
     * Creates adjustment movements for all lines with differences.
     *
     * @param CycleCount $cycleCount
     * @return CycleCount
     * @throws \InvalidArgumentException
     */
    public function complete(CycleCount $cycleCount): CycleCount
    {
        if (!$cycleCount->canComplete()) {
            throw new \InvalidArgumentException(
                "No se puede completar un conteo en estado '{$cycleCount->status->label()}'."
            );
        }

        // Check all lines are counted
        $uncountedLines = $cycleCount->lines()->whereNull('counted_qty')->count();
        if ($uncountedLines > 0) {
            throw new \InvalidArgumentException(
                "Hay {$uncountedLines} líneas sin contar. Complete todas las líneas antes de cerrar."
            );
        }

        return DB::transaction(function () use ($cycleCount) {
            $adjustmentsCount = 0;

            // Get lines with differences
            $linesWithDifferences = $cycleCount->lines()
                ->with('inventoryItem')
                ->where('difference_qty', '!=', 0)
                ->get();

            foreach ($linesWithDifferences as $line) {
                $item = $line->inventoryItem;

                // Create adjustment movement
                $this->movementService->adjust(
                    $item,
                    (float) $line->counted_qty,
                    'cycle_count',
                    "Cycle Count #{$cycleCount->id} - Diferencia: {$line->difference_qty}"
                );

                $adjustmentsCount++;
            }

            // Mark as completed
            $cycleCount->update([
                'status' => CycleCountStatus::Completed,
                'completed_at' => now(),
            ]);

            Log::info('Cycle count completed with reconciliation', [
                'cycle_count_id' => $cycleCount->id,
                'adjustments_count' => $adjustmentsCount,
                'total_abs_difference' => $cycleCount->totalAbsoluteDifference(),
            ]);

            return $cycleCount;
        });
    }

    /**
     * Cancel the cycle count.
     *
     * @param CycleCount $cycleCount
     * @param string|null $reason
     * @return CycleCount
     * @throws \InvalidArgumentException
     */
    public function cancel(CycleCount $cycleCount, ?string $reason = null): CycleCount
    {
        if (!$cycleCount->canCancel()) {
            throw new \InvalidArgumentException(
                "No se puede cancelar un conteo en estado '{$cycleCount->status->label()}'."
            );
        }

        $cycleCount->update([
            'status' => CycleCountStatus::Cancelled,
            'notes' => $reason ? ($cycleCount->notes . "\nCancelado: " . $reason) : $cycleCount->notes,
        ]);

        Log::info('Cycle count cancelled', [
            'cycle_count_id' => $cycleCount->id,
            'reason' => $reason,
        ]);

        return $cycleCount;
    }
}
