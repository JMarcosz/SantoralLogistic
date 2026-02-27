<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StorePackageTypeRequest;
use App\Http\Requests\Settings\UpdatePackageTypeRequest;
use App\Models\PackageType;
use Inertia\Inertia;
use Inertia\Response;

class PackageTypeController extends Controller
{
    /**
     * Display a listing of the package types.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', PackageType::class);

        return Inertia::render('settings/package-types/index', [
            'packageTypes' => PackageType::orderBy('code')->get(),
        ]);
    }

    /**
     * Store a newly created package type in storage.
     */
    public function store(StorePackageTypeRequest $request)
    {
        $this->authorize('create', PackageType::class);

        PackageType::create($request->validated());

        return back()->with('success', 'Tipo de paquete creado correctamente.');
    }

    /**
     * Update the specified package type in storage.
     */
    public function update(UpdatePackageTypeRequest $request, PackageType $packageType)
    {
        $this->authorize('update', $packageType);

        $packageType->update($request->validated());

        return back()->with('success', 'Tipo de paquete actualizado correctamente.');
    }

    /**
     * Remove the specified package type from storage (soft delete).
     */
    public function destroy(PackageType $packageType)
    {
        $this->authorize('delete', $packageType);

        $packageType->delete();

        return back()->with('success', 'Tipo de paquete eliminado correctamente.');
    }
}
