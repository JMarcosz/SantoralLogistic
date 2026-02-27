<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPDStateTransitionException;
use App\Http\Requests\StorePodRequest;
use App\Http\Requests\StorePickupOrderRequest;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\PickupOrder;
use App\Models\Pod;
use App\Models\ShippingOrder;
use App\Services\PickupOrderStateMachine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PickupOrderController extends Controller
{
    public function __construct(
        protected PickupOrderStateMachine $stateMachine
    ) {}

    /**
     * Display a listing of pickup orders.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PickupOrder::class);

        $query = PickupOrder::with(['customer', 'driver', 'shippingOrder'])
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

        return Inertia::render('pickup-orders/index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'scheduled_date', 'driver_id']),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new pickup order.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', PickupOrder::class);

        return Inertia::render('pickup-orders/create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'code']),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
            'shippingOrders' => ShippingOrder::orderBy('order_number', 'desc')
                ->limit(100)
                ->get(['id', 'order_number', 'customer_id']),
            'shippingOrderId' => $request->query('shipping_order_id'),
        ]);
    }

    /**
     * Store a newly created pickup order.
     */
    public function store(StorePickupOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $stops = $validated['stops'] ?? [];
        unset($validated['stops']);

        $order = PickupOrder::create($validated);

        // Create stops if provided
        foreach ($stops as $index => $stop) {
            $order->stops()->create([
                ...$stop,
                'sequence' => $index + 1,
            ]);
        }

        return redirect()
            ->route('pickup-orders.show', $order)
            ->with('success', 'Orden de recogida creada exitosamente.');
    }

    /**
     * Display the specified pickup order.
     */
    public function show(PickupOrder $pickupOrder): Response
    {
        $this->authorize('view', $pickupOrder);

        $pickupOrder->load(['customer', 'driver', 'shippingOrder', 'stops', 'pod.createdBy']);

        return Inertia::render('pickup-orders/show', [
            'order' => $pickupOrder,
            'allowedTransitions' => $this->stateMachine->getAllowedTransitions($pickupOrder),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
            'canRegisterPod' => $this->stateMachine->canRegisterPod($pickupOrder),
        ]);
    }

    /**
     * Show the form for editing the pickup order.
     */
    public function edit(PickupOrder $pickupOrder): Response
    {
        $this->authorize('update', $pickupOrder);

        $pickupOrder->load(['stops']);

        return Inertia::render('pickup-orders/edit', [
            'order' => $pickupOrder,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'code']),
            'drivers' => Driver::active()->orderBy('name')->get(['id', 'name']),
            'shippingOrders' => ShippingOrder::orderBy('order_number', 'desc')
                ->limit(100)
                ->get(['id', 'order_number', 'customer_id']),
        ]);
    }

    /**
     * Update the specified pickup order.
     */
    public function update(StorePickupOrderRequest $request, PickupOrder $pickupOrder): RedirectResponse
    {
        $this->authorize('update', $pickupOrder);

        $validated = $request->validated();
        $stops = $validated['stops'] ?? null;
        unset($validated['stops']);

        $pickupOrder->update($validated);

        // Update stops if provided
        if ($stops !== null) {
            $pickupOrder->stops()->delete();
            foreach ($stops as $index => $stop) {
                $pickupOrder->stops()->create([
                    ...$stop,
                    'sequence' => $index + 1,
                ]);
            }
        }

        return redirect()
            ->route('pickup-orders.show', $pickupOrder)
            ->with('success', 'Orden de recogida actualizada.');
    }

    /**
     * Remove the specified pickup order.
     */
    public function destroy(PickupOrder $pickupOrder): RedirectResponse
    {
        $this->authorize('delete', $pickupOrder);

        $pickupOrder->stops()->delete();
        $pickupOrder->delete();

        return redirect()
            ->route('pickup-orders.index')
            ->with('success', 'Orden de recogida eliminada.');
    }

    /**
     * Assign a driver to the pickup order.
     */
    public function assignDriver(Request $request, PickupOrder $pickupOrder): RedirectResponse
    {
        $this->authorize('assignDriver', $pickupOrder);

        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $driver = Driver::findOrFail($request->driver_id);

        try {
            $this->stateMachine->assign($pickupOrder, $driver);

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
     * Start the pickup (driver begins route).
     */
    public function start(PickupOrder $pickupOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $pickupOrder);

        try {
            $this->stateMachine->start($pickupOrder);

            return redirect()
                ->back()
                ->with('success', 'Recogida iniciada.');
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Complete the pickup.
     */
    public function complete(PickupOrder $pickupOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $pickupOrder);

        try {
            $this->stateMachine->complete($pickupOrder);

            return redirect()
                ->back()
                ->with('success', 'Recogida completada.');
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the pickup order.
     */
    public function cancel(PickupOrder $pickupOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $pickupOrder);

        try {
            $this->stateMachine->cancel($pickupOrder);

            return redirect()
                ->back()
                ->with('success', 'Orden de recogida cancelada.');
        } catch (InvalidPDStateTransitionException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Register POD (Proof of Delivery) for the pickup order.
     * 
     * This uses the StateMachine's completeWithPod which handles:
     * - State validation (must be in_progress)
     * - Idempotency check (no duplicate POD)
     * - DB transaction (POD creation + state change atomic)
     */
    public function storePod(StorePodRequest $request, PickupOrder $pickupOrder): RedirectResponse
    {
        $this->authorize('changeStatus', $pickupOrder);

        $validated = $request->validated();

        try {
            $this->stateMachine->completeWithPod(
                $pickupOrder,
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
