<?php

namespace App\Http\Controllers;

use App\Models\CycleCount;
use App\Models\CycleCountLine;
use App\Models\Warehouse;
use App\Services\CycleCountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CycleCountController extends Controller
{
    public function __construct(
        private CycleCountService $cycleCountService
    ) {}

    /**
     * Display a listing of cycle counts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CycleCount::class);

        $query = CycleCount::with(['warehouse', 'createdBy'])
            ->withCount('lines');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $counts = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('cycleCounts/index', [
            'counts' => $counts,
            'filters' => $request->only(['status', 'warehouse_id']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * Show the form for creating a new cycle count.
     */
    public function create(): Response
    {
        $this->authorize('create', CycleCount::class);

        $warehouses = Warehouse::orderBy('name')->get(['id', 'name', 'code']);

        // Get customers that have inventory
        $customers = \App\Models\Customer::whereHas('inventoryItems')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('cycleCounts/create', [
            'warehouses' => $warehouses,
            'customers' => $customers,
        ]);
    }

    /**
     * Store a newly created cycle count.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CycleCount::class);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'reference' => 'nullable|string|max:100',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'filters' => 'nullable|array',
            'filters.customer_id' => 'nullable|integer|exists:customers,id',
            'filters.sku' => 'nullable|string|max:100',
            'filters.location_id' => 'nullable|integer|exists:locations,id',
        ]);

        try {
            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

            $cycleCount = $this->cycleCountService->create(
                $warehouse,
                $validated['filters'] ?? [],
                $validated['reference'] ?? null,
                $validated['scheduled_at'] ? new \DateTime($validated['scheduled_at']) : null,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('cycle-counts.show', $cycleCount)
                ->with('success', "Conteo cíclico creado con {$cycleCount->totalLines()} líneas.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified cycle count.
     */
    public function show(CycleCount $cycleCount): Response
    {
        $this->authorize('view', $cycleCount);

        $cycleCount->load(['warehouse', 'createdBy', 'lines.inventoryItem.location']);

        return Inertia::render('cycleCounts/show', [
            'cycleCount' => [
                'id' => $cycleCount->id,
                'warehouse' => $cycleCount->warehouse,
                'status' => $cycleCount->status->value,
                'status_label' => $cycleCount->status->label(),
                'status_color' => $cycleCount->status->color(),
                'reference' => $cycleCount->reference,
                'scheduled_at' => $cycleCount->scheduled_at?->format('Y-m-d H:i'),
                'completed_at' => $cycleCount->completed_at?->format('Y-m-d H:i'),
                'notes' => $cycleCount->notes,
                'created_by' => $cycleCount->createdBy?->name,
                'created_at' => $cycleCount->created_at->format('Y-m-d H:i'),
                'lines' => $cycleCount->lines->map(fn($line) => [
                    'id' => $line->id,
                    'sku' => $line->inventoryItem->item_code,
                    'description' => $line->inventoryItem->description,
                    'location' => $line->inventoryItem->location?->code,
                    'expected_qty' => $line->expected_qty,
                    'counted_qty' => $line->counted_qty,
                    'difference_qty' => $line->difference_qty,
                    'is_counted' => $line->isCounted(),
                    'difference_type' => $line->differenceType(),
                ]),
                'total_lines' => $cycleCount->totalLines(),
                'counted_lines' => $cycleCount->countedLinesCount(),
                'counting_progress' => $cycleCount->countingProgress(),
                'lines_with_differences' => $cycleCount->linesWithDifferences(),
                'can_start' => $cycleCount->canStart(),
                'can_complete' => $cycleCount->canComplete(),
                'can_cancel' => $cycleCount->canCancel(),
            ],
        ]);
    }

    /**
     * Start the counting process.
     */
    public function start(CycleCount $cycleCount): RedirectResponse
    {
        $this->authorize('update', $cycleCount);

        try {
            $this->cycleCountService->start($cycleCount);
            return back()->with('success', 'Conteo iniciado. Puede comenzar a registrar cantidades.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update a line's counted quantity.
     */
    public function updateLine(Request $request, CycleCount $cycleCount, CycleCountLine $line): RedirectResponse
    {
        $this->authorize('update', $cycleCount);

        if ($line->cycle_count_id !== $cycleCount->id) {
            return back()->with('error', 'La línea no pertenece a este conteo.');
        }

        $validated = $request->validate([
            'counted_qty' => 'required|numeric|min:0',
        ]);

        try {
            $this->cycleCountService->updateLine($line, (float) $validated['counted_qty']);
            return back()->with('success', 'Cantidad registrada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the cycle count and reconcile differences.
     */
    public function complete(CycleCount $cycleCount): RedirectResponse
    {
        $this->authorize('complete', $cycleCount);

        try {
            $this->cycleCountService->complete($cycleCount);
            $adjustments = $cycleCount->linesWithDifferences();
            return back()->with('success', "Conteo completado. Se generaron {$adjustments} ajustes de inventario.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the cycle count.
     */
    public function cancel(Request $request, CycleCount $cycleCount): RedirectResponse
    {
        $this->authorize('cancel', $cycleCount);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->cycleCountService->cancel($cycleCount, $validated['reason'] ?? null);
            return back()->with('success', 'Conteo cancelado.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
