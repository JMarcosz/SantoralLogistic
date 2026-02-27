<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryReservationRequest;
use App\Models\ShippingOrder;
use App\Services\InventoryReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class InventoryReservationController extends Controller
{
    public function __construct(
        private InventoryReservationService $reservationService
    ) {}

    /**
     * List all reservations for a shipping order.
     */
    public function index(ShippingOrder $shippingOrder): JsonResponse
    {
        $this->authorize('view', $shippingOrder);

        $reservations = $this->reservationService->getReservationsForShippingOrder($shippingOrder);

        return response()->json([
            'shipping_order_id' => $shippingOrder->id,
            'order_number' => $shippingOrder->order_number,
            'reservations' => $reservations->map(fn($r) => [
                'id' => $r->id,
                'inventory_item_id' => $r->inventory_item_id,
                'sku' => $r->inventoryItem->item_code,
                'description' => $r->inventoryItem->description,
                'qty_reserved' => $r->qty_reserved,
                'warehouse' => $r->inventoryItem->warehouse?->name,
                'location' => $r->inventoryItem->location?->code,
                'created_at' => $r->created_at->format('Y-m-d H:i'),
            ]),
            'total_reserved' => $reservations->sum('qty_reserved'),
        ]);
    }

    /**
     * Create inventory reservations for a shipping order.
     */
    public function store(StoreInventoryReservationRequest $request, ShippingOrder $shippingOrder): RedirectResponse|JsonResponse
    {
        try {
            $reservations = $this->reservationService->reserveForShippingOrder(
                $shippingOrder,
                $request->validated('lines')
            );

            $message = 'Se reservaron ' . count($reservations) . ' líneas de inventario correctamente.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'reservations_count' => count($reservations),
                ]);
            }

            return back()->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Release all reservations for a shipping order.
     */
    public function destroy(ShippingOrder $shippingOrder): RedirectResponse|JsonResponse
    {
        $this->authorize('reserveInventory', $shippingOrder);

        $count = $this->reservationService->releaseReservationsForShippingOrder($shippingOrder);

        $message = $count > 0
            ? "Se liberaron {$count} reservas de inventario."
            : 'No había reservas para liberar.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'released_count' => $count,
            ]);
        }

        return back()->with('success', $message);
    }
}
