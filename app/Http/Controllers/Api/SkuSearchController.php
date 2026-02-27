<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkuSearchController extends Controller
{
    /**
     * Search for products/services by code or name.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $results = ProductService::active()
            ->products()
            ->where(function ($q) use ($query) {
                $q->where('code', 'like', "%{$query}%")
                    ->orWhere('name', 'like', "%{$query}%");
            })
            ->orderBy('code')
            ->limit(20)
            ->get(['id', 'code', 'name', 'description', 'uom']);

        return response()->json($results->map(fn($p) => [
            'id' => $p->id,
            'sku' => $p->code,
            'name' => $p->name,
            'description' => $p->description ?? $p->name,
            'uom' => $p->uom ?? 'PCS',
        ]));
    }
}
