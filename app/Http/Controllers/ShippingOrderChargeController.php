<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChargeRequest;
use App\Http\Requests\UpdateChargeRequest;
use App\Models\Charge;
use App\Models\ShippingOrder;
use App\Services\ShippingOrderChargeService;
use Illuminate\Http\RedirectResponse;

class ShippingOrderChargeController extends Controller
{
    public function __construct(
        protected ShippingOrderChargeService $chargeService
    ) {}

    /**
     * Store a newly created charge.
     */
    public function store(StoreChargeRequest $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        // Authorization handled in FormRequest

        try {
            $this->chargeService->createCharge($shippingOrder, $request->validated());
            return back()->with('success', 'Cargo agregado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified charge.
     */
    public function update(UpdateChargeRequest $request, ShippingOrder $shippingOrder, Charge $charge): RedirectResponse
    {
        try {
            $this->chargeService->updateCharge($charge, $request->validated());
            return back()->with('success', 'Cargo actualizado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified charge.
     */
    public function destroy(ShippingOrder $shippingOrder, Charge $charge): RedirectResponse
    {
        // Verify authorization
        $this->authorize('manageCharges', $shippingOrder);

        // Verify charge belongs to this order
        if ($charge->shipping_order_id !== $shippingOrder->id) {
            return back()->with('error', 'El cargo no pertenece a esta orden.');
        }

        try {
            $this->chargeService->deleteCharge($charge);
            return back()->with('success', 'Cargo eliminado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
