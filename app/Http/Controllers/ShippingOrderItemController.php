<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShippingOrderItemRequest;
use App\Models\ShippingOrder;
use App\Models\ShippingOrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ShippingOrderItemController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShippingOrderItemRequest $request, ShippingOrder $shippingOrder): RedirectResponse
    {
        DB::transaction(function () use ($request, $shippingOrder) {
            $itemData = $request->safe()->only(['type', 'identifier', 'seal_number', 'properties']);

            $item = $shippingOrder->items()->create($itemData);

            $lines = $request->safe()->only(['lines'])['lines'];
            $item->lines()->createMany($lines);

            $shippingOrder->calculateTotalsFromItems();
        });

        return back()->with('success', 'Item added successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShippingOrder $shippingOrder, ShippingOrderItem $item): RedirectResponse
    {
        if ($item->shipping_order_id !== $shippingOrder->id) {
            abort(404);
        }

        DB::transaction(function () use ($shippingOrder, $item) {
            $item->delete();
            $shippingOrder->calculateTotalsFromItems();
        });

        return back()->with('success', 'Item removed successfully.');
    }
}
