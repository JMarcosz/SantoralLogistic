<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreWarehouseRequest;
use App\Http\Requests\Settings\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Warehouse::class);

        return Inertia::render('settings/warehouses/index', [
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(StoreWarehouseRequest $request)
    {
        $this->authorize('create', Warehouse::class);

        Warehouse::create($request->validated());

        return back()->with('success', 'Almacén creado correctamente.');
    }

    /**
     * Update the specified warehouse.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return back()->with('success', 'Almacén actualizado correctamente.');
    }

    /**
     * Toggle warehouse active status.
     */
    public function destroy(Warehouse $warehouse)
    {
        $this->authorize('delete', $warehouse);

        $warehouse->update(['is_active' => !$warehouse->is_active]);

        $message = $warehouse->is_active ? 'Almacén activado.' : 'Almacén desactivado.';
        return back()->with('success', $message);
    }
}
