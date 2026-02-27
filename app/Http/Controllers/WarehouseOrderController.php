<?php

namespace App\Http\Controllers;

use App\Models\ShippingOrder;
use App\Models\Warehouse;
use App\Models\WarehouseOrder;
use App\Models\WarehouseOrderLine;
use App\Services\WarehouseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseOrderController extends Controller
{
    public function __construct(
        private WarehouseOrderService $warehouseOrderService
    ) {}

    /**
     * Display a listing of warehouse orders.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WarehouseOrder::class);

        $query = WarehouseOrder::with(['warehouse', 'shippingOrder.customer', 'createdBy'])
            ->withCount('lines');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // Transform data for frontend
        $orders->getCollection()->transform(fn($order) => [
            'id' => $order->id,
            'warehouse' => $order->warehouse,
            'shipping_order' => [
                'id' => $order->shippingOrder->id,
                'order_number' => $order->shippingOrder->order_number,
                'customer' => $order->shippingOrder->customer,
            ],
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_color' => $order->status->color(),
            'reference' => $order->reference,
            'lines_count' => $order->lines_count,
            'created_at' => $order->created_at->format('Y-m-d H:i'),
        ]);

        return Inertia::render('warehouseOrders/index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'warehouse_id']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }


    /**
     * Display the specified warehouse order.
     */
    public function show(WarehouseOrder $warehouseOrder): Response
    {
        $this->authorize('view', $warehouseOrder);

        $warehouseOrder->load([
            'warehouse',
            'shippingOrder.customer',
            'deliveryOrder',
            'createdBy',
            'lines.inventoryItem.location',
        ]);

        return Inertia::render('warehouseOrders/show', [
            'warehouseOrder' => [
                'id' => $warehouseOrder->id,
                'warehouse' => $warehouseOrder->warehouse,
                'shipping_order' => [
                    'id' => $warehouseOrder->shippingOrder->id,
                    'order_number' => $warehouseOrder->shippingOrder->order_number,
                    'customer_name' => $warehouseOrder->shippingOrder->customer?->name,
                ],
                'delivery_order_id' => $warehouseOrder->delivery_order_id,
                'status' => $warehouseOrder->status->value,
                'status_label' => $warehouseOrder->status->label(),
                'status_color' => $warehouseOrder->status->color(),
                'reference' => $warehouseOrder->reference,
                'notes' => $warehouseOrder->notes,
                'created_by' => $warehouseOrder->createdBy?->name,
                'created_at' => $warehouseOrder->created_at->format('Y-m-d H:i'),
                'lines' => $warehouseOrder->lines->map(fn($line) => [
                    'id' => $line->id,
                    'sku' => $line->sku,
                    'description' => $line->description,
                    'qty_to_pick' => $line->qty_to_pick,
                    'qty_picked' => $line->qty_picked,
                    'uom' => $line->uom,
                    'location_code' => $line->location_code,
                    'is_fully_picked' => $warehouseOrder->isDispatched() || $line->isFullyPicked(),
                    'progress' => $warehouseOrder->isDispatched() ? 100 : $line->pickingProgress(),
                ]),
                'total_qty_to_pick' => $warehouseOrder->totalQtyToPick(),
                'total_qty_picked' => $warehouseOrder->totalQtyPicked(),
                'picking_progress' => $warehouseOrder->pickingProgress(),
                'can_start_picking' => $warehouseOrder->canStartPicking(),
                'can_mark_packed' => $warehouseOrder->canMarkPacked(),
                'can_mark_dispatched' => $warehouseOrder->canMarkDispatched(),
                'can_cancel' => $warehouseOrder->canCancel(),
            ],
        ]);
    }

    /**
     * Create a warehouse order from a shipping order.
     */
    public function storeFromShippingOrder(Request $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        $this->authorize('create', WarehouseOrder::class);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

            $warehouseOrder = $this->warehouseOrderService->createFromShippingOrder(
                $shippingOrder,
                $warehouse,
                $validated['reference'] ?? null,
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('warehouse-orders.show', $warehouseOrder)
                ->with('success', 'Orden de almacén creada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Start the picking process.
     */
    public function startPicking(WarehouseOrder $warehouseOrder): RedirectResponse
    {
        $this->authorize('update', $warehouseOrder);

        try {
            $this->warehouseOrderService->startPicking($warehouseOrder);
            return back()->with('success', 'Picking iniciado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update a line's picked quantity.
     */
    public function updateLine(Request $request, WarehouseOrder $warehouseOrder, WarehouseOrderLine $line): RedirectResponse
    {
        $this->authorize('update', $warehouseOrder);

        // Ensure line belongs to this order
        if ($line->warehouse_order_id !== $warehouseOrder->id) {
            return back()->with('error', 'La línea no pertenece a esta orden.');
        }

        $validated = $request->validate([
            'qty_picked' => 'required|numeric|min:0|max:' . $line->qty_to_pick,
        ]);

        try {
            $this->warehouseOrderService->updateLinePicked(
                $line,
                (float) $validated['qty_picked']
            );
            return back()->with('success', 'Cantidad actualizada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark order as packed.
     */
    public function markPacked(WarehouseOrder $warehouseOrder): RedirectResponse
    {
        $this->authorize('update', $warehouseOrder);

        try {
            $this->warehouseOrderService->markPacked($warehouseOrder);
            return back()->with('success', 'Orden marcada como empacada.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark order as dispatched.
     */
    public function markDispatched(Request $request, WarehouseOrder $warehouseOrder): RedirectResponse
    {
        $this->authorize('dispatch', $warehouseOrder);

        $validated = $request->validate([
            'delivery_order_id' => 'nullable|exists:delivery_orders,id',
        ]);

        try {
            $this->warehouseOrderService->markDispatched(
                $warehouseOrder,
                $validated['delivery_order_id'] ?? null
            );
            return back()->with('success', 'Orden despachada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the warehouse order.
     */
    public function cancel(Request $request, WarehouseOrder $warehouseOrder): RedirectResponse
    {
        $this->authorize('cancel', $warehouseOrder);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->warehouseOrderService->cancel(
                $warehouseOrder,
                $validated['reason'] ?? null
            );
            return back()->with('success', 'Orden cancelada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
