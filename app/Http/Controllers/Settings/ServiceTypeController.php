<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreServiceTypeRequest;
use App\Http\Requests\Settings\UpdateServiceTypeRequest;
use App\Models\ServiceType;
use Inertia\Inertia;
use Inertia\Response;

class ServiceTypeController extends Controller
{
    /**
     * Display a listing of the service types.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ServiceType::class);

        return Inertia::render('settings/service-types/index', [
            'serviceTypes' => ServiceType::orderBy('code')->get(),
        ]);
    }

    /**
     * Store a newly created service type in storage.
     */
    public function store(StoreServiceTypeRequest $request)
    {
        $this->authorize('create', ServiceType::class);

        ServiceType::create($request->validated());

        return back()->with('success', 'Tipo de servicio creado correctamente.');
    }

    /**
     * Update the specified service type in storage.
     */
    public function update(UpdateServiceTypeRequest $request, ServiceType $serviceType)
    {
        $this->authorize('update', $serviceType);

        $serviceType->update($request->validated());

        return back()->with('success', 'Tipo de servicio actualizado correctamente.');
    }

    /**
     * Remove the specified service type from storage (soft delete).
     */
    public function destroy(ServiceType $serviceType)
    {
        $this->authorize('delete', $serviceType);

        // Prevent deletion of default service type
        if ($serviceType->is_default) {
            return back()->with('error', 'No se puede eliminar el tipo de servicio predeterminado.');
        }

        $serviceType->delete();

        return back()->with('success', 'Tipo de servicio eliminado correctamente.');
    }
}
