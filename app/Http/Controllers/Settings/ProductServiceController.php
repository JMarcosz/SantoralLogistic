<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreProductServiceRequest;
use App\Http\Requests\Settings\UpdateProductServiceRequest;
use App\Models\Currency;
use App\Models\ProductService;
use Inertia\Inertia;
use Inertia\Response;

class ProductServiceController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ProductService::class);

        return Inertia::render('settings/products-services/index', [
            'productsServices' => ProductService::with('defaultCurrency')->orderBy('code')->get(),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol']),
        ]);
    }

    public function store(StoreProductServiceRequest $request)
    {
        $this->authorize('create', ProductService::class);

        ProductService::create($request->validated());

        return back()->with('success', 'Producto/Servicio creado correctamente.');
    }

    public function update(UpdateProductServiceRequest $request, ProductService $productsService)
    {
        $this->authorize('update', $productsService);

        $productsService->update($request->validated());

        return back()->with('success', 'Producto/Servicio actualizado correctamente.');
    }

    public function destroy(ProductService $productsService)
    {
        $this->authorize('delete', $productsService);

        $productsService->delete();

        return back()->with('success', 'Producto/Servicio eliminado correctamente.');
    }
}
