<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPDStateTransitionException;
use App\Http\Requests\StorePodRequest;
use App\Http\Requests\StoreDeliveryOrderRequest;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Pod;
use App\Models\ShippingOrder;
use App\Services\DeliveryOrderStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryOrderController extends Controller
{
    public function __construct(
        protected DeliveryOrderStateMachine $stateMachine
    ) {}

    /**
     * Display a listing of delivery orders.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DeliveryOrder::class);

        $query = DeliveryOrder::with(['customer', 'driver', 'shippingOrder'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->scheduled_date);
        }

        // Filter by driver
        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        $orders = $query->paginate(20)->withQueryString();

        return Inertia::render('delivery-orders/index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'scheduled_date', 'driver_id']),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new delivery order.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', DeliveryOrder::class);

        return Inertia::render('delivery-orders/create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'code']),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
            'shippingOrders' => ShippingOrder::orderBy('order_number', 'desc')
                ->limit(100)
                ->get(['id', 'order_number', 'customer_id']),
            'shippingOrderId' => $request->query('shipping_order_id'),
        ]);
    }

    /**
     * Store a newly created delivery order.
     */
    public function store(StoreDeliveryOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $stops = $validated['stops'] ?? [];
        unset($validated['stops']);

        $order = DeliveryOrder::create($validated);

        // Create stops if provided
        foreach ($stops as $index => $stop) {
            $order->stops()->create([
                ...$stop,
                'sequence' => $index + 1,
            ]);
        }

        return redirect()
            ->route('delivery-orders.show', $order)
            ->with('success', 'Orden de entrega creada exitosamente.');
    }

    /**
     * Display the specified delivery order.
     */
    public function show(DeliveryOrder $deliveryOrder): Response
    {
        $this->authorize('view', $deliveryOrder);

        $deliveryOrder->load(['customer', 'driver', 'shippingOrder', 'stops', 'pod.createdBy']);

        return Inertia::render('delivery-orders/show', [
            'order' => $deliveryOrder,
            'allowedTransitions' => $this->stateMachine->getAllowedTransitions($deliveryOrder),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
            'canRegisterPod' => $this->stateMachine->canRegisterPod($deliveryOrder),
        ]);
    }

    /**
     * Show the form for editing the delivery order.
     */
    public function edit(DeliveryOrder $deliveryOrder): Response
    {
        $this->authorize('update', $deliveryOrder);

        $deliveryOrder->load(['stops']);

        return Inertia::render('delivery-orders/edit', [
            'order' => $deliveryOrder,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'code']),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
            'shippingOrders' => ShippingOrder::orderBy('order_number', 'desc')
                ->limit(100)
                ->get(['id', 'order_number', 'customer_id']),
        ]);
    }

    /**
     * Update the specified delivery order.
     */
    public function update(StoreDeliveryOrderRequest $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorize('update', $deliveryOrder);

        $validated = $request->validated();
        $stops = $validated['stops'] ?? null;
        unset($validated['stops']);

        $deliveryOrder->update($validated);

        // Update stops if provided
        if ($stops !== null) {
            $deliveryOrder->stops()->delete();
            foreach ($stops as $index => $stop) {
                $deliveryOrder->stops()->create([
                    ...$stop,
                    'sequence' => $index + 1,
                ]);
            }
        }

        return redirect()
            ->route('delivery-orders.show', $deliveryOrder)
            ->with('success', 'Orden de entrega actualizada.');
    }

    /**
     * Remove the specified delivery order.
     */
    public function destroy(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorize('delete', $deliveryOrder);

        $deliveryOrder->stops()->delete();
        $deliveryOrder->delete();

        return redirect()
            ->route('delivery-orders.index')
            ->with('success', 'Orden de entrega eliminada.');
    }

    /**
     * Assign a driver to the delivery order.
     */
    public function assignDriver(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorize('assignDriver', $deliveryOrder);

        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $driver = Driver::findOrFail($request->driver_id);

        try {
            $this->stateMachine->assign($deliveryOrder, $driver);

            return redirect()
                ->back()
                ->with('success', "Conductor {$driver->name} asignado exitosamente.");
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Start the delivery (driver begins route).
     */
    public function start(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $deliveryOrder);

        try {
            $this->stateMachine->start($deliveryOrder);

            return redirect()
                ->back()
                ->with('success', 'Entrega iniciada.');
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the delivery.
     */
    public function complete(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $deliveryOrder);

        try {
            $this->stateMachine->complete($deliveryOrder);

            return redirect()
                ->back()
                ->with('success', 'Entrega completada.');
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the delivery order.
     */
    public function cancel(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $deliveryOrder);

        try {
            $this->stateMachine->cancel($deliveryOrder);

            return redirect()
                ->back()
                ->with('success', 'Orden de entrega cancelada.');
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Register POD (Proof of Delivery) for the delivery order.
     * 
     * This uses the StateMachine's completeWithPod which handles:
     * - State validation (must be in_progress)
     * - Idempotency check (no duplicate POD)
     * - DB transaction (POD creation + state change atomic)
     */
    public function storePod(StorePodRequest $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $deliveryOrder);

        $validated = $request->validated();

        try {
            $this->stateMachine->completeWithPod(
                $deliveryOrder,
                [
                    'happened_at' => $validated['happened_at'],
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                ],
                $request->file('image')
            );

            return redirect()
                ->back()
                ->with('success', 'POD registrado exitosamente. La orden ha sido completada.');
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation (race condition protection)
            if (
                str_contains($e->getMessage(), 'UNIQUE constraint') ||
                str_contains($e->getMessage(), 'Duplicate entry')
            ) {
                return redirect()
                    ->back()
                    ->with('error', 'Esta orden ya tiene un POD registrado.');
            }
            throw $e;
        }
    }
}
