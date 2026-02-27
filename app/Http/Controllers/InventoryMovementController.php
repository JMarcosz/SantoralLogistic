<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdjustRequest;
use App\Http\Requests\StorePutawayRequest;
use App\Http\Requests\StoreRelocateRequest;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Warehouse;
use App\Services\InventoryMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class InventoryMovementController extends Controller
{
    public function __construct(
        private InventoryMovementService $movementService
    ) {}

    /**
     * Putaway: Assign location to an unlocated item.
     */
    public function putaway(StorePutawayRequest $request, InventoryItem $item): RedirectResponse
    {
        try {
            $location = Location::findOrFail($request->validated('location_id'));
            $this->movementService->putaway($item, $location);

            return back()->with('success', 'Ubicación asignada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Relocate: Move quantity to another location.
     */
    public function relocate(StoreRelocateRequest $request, InventoryItem $item): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $toLocation = Location::findOrFail($validated['to_location_id']);

            $this->movementService->relocate(
                $item,
                $toLocation,
                (float) $validated['qty'],
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Inventario reubicado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Adjust: Change quantity with reason.
     */
    public function adjust(StoreAdjustRequest $request, InventoryItem $item): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $this->movementService->adjust(
                $item,
                (float) $validated['new_qty'],
                $validated['reason'],
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Cantidad ajustada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get movement history for an item.
     */
    public function movements(InventoryItem $item): JsonResponse
    {
        $this->authorize('viewMovements', $item);

        $movements = $this->movementService->getMovements($item);

        return response()->json([
            'item' => [
                'id' => $item->id,
                'sku' => $item->item_code,
                'description' => $item->description,
                'qty' => $item->qty,
            ],
            'movements' => $movements->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->movement_type->value,
                'type_label' => $m->movement_type->label(),
                'from_location' => $m->fromLocation?->code,
                'to_location' => $m->toLocation?->code,
                'qty' => $m->qty,
                'reference' => $m->reference,
                'notes' => $m->notes,
                'user' => $m->user?->name,
                'created_at' => $m->created_at->format('Y-m-d H:i'),
            ]),
        ]);
    }

    /**
     * Get locations for a warehouse (for dropdowns).
     */
    public function locations(Warehouse $warehouse): JsonResponse
    {
        $locations = $warehouse->locations()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'zone', 'type']);

        return response()->json($locations);
    }
}
