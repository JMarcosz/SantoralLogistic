<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerItemController extends Controller
{
    /**
     * Search customer items.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'q' => 'nullable|string',
        ]);

        $customerId = $request->input('customer_id');
        $query = $request->input('q');

        $items = CustomerItem::active()
            ->where('customer_id', $customerId)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('code', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });
            })
            ->limit(20)
            ->get(['id', 'code', 'description', 'default_uom']);

        return response()->json($items->map(fn($item) => [
            'id' => $item->id,
            'code' => $item->code,
            'description' => $item->description,
            'uom' => $item->default_uom,
        ]));
    }
}
