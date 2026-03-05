<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductService;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductServiceApiController extends Controller
{
    /**
     * Get products/services filtered by type.
     * GET /api/products-services?type=product|service|fee
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductService::active()->orderBy('code');

        if ($request->filled('type')) {
            $type = $request->input('type');
            if ($type === 'service') {
                // Include both services and fees
                $query->whereIn('type', ['service', 'fee']);
            } else {
                $query->ofType($type);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $items = $query->select('id', 'code', 'name', 'description', 'type', 'uom', 'default_unit_price', 'taxable')
            ->get();

        return response()->json($items);
    }

    /**
     * Get only active products for warehouse receipt dropdown.
     * GET /api/products-services/products
     */
    public function products(): JsonResponse
    {
        $products = ProductService::active()
            ->products()
            ->select('id', 'code', 'name', 'description', 'uom', 'default_unit_price', 'taxable')
            ->orderBy('code')
            ->get();

        return response()->json($products);
    }

    /**
     * Get products with aggregated stock from inventory.
     * GET /api/products-services/products-with-stock
     */
    public function productsWithStock(Request $request): JsonResponse
    {
        $products = ProductService::active()
            ->products()
            ->withSum('inventoryItems as total_stock', 'qty')
            ->select('id', 'code', 'name', 'description', 'uom', 'default_unit_price', 'taxable')
            ->orderBy('code')
            ->get()
            ->map(function ($product) {
                $product->total_stock = (float) ($product->total_stock ?? 0);
                return $product;
            });

        return response()->json($products);
    }
}
