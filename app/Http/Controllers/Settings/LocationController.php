<?php

namespace App\Http\Controllers\Settings;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreLocationRequest;
use App\Http\Requests\Settings\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Warehouse;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Location::class);

        return Inertia::render('settings/locations/index', [
            'locations' => Location::with('warehouse')->orderBy('warehouse_id')->orderBy('code')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name', 'code']),
            'locationTypes' => collect(LocationType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ]),
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(StoreLocationRequest $request)
    {
        $this->authorize('create', Location::class);

        Location::create($request->validated());

        return back()->with('success', 'Ubicación creada correctamente.');
    }

    /**
     * Update the specified location.
     */
    public function update(UpdateLocationRequest $request, Location $location)
    {
        $this->authorize('update', $location);

        $location->update($request->validated());

        return back()->with('success', 'Ubicación actualizada correctamente.');
    }

    /**
     * Toggle location active status.
     */
    public function destroy(Location $location)
    {
        $this->authorize('delete', $location);

        $location->update(['is_active' => !$location->is_active]);

        $message = $location->is_active ? 'Ubicación activada.' : 'Ubicación desactivada.';
        return back()->with('success', $message);
    }
}
