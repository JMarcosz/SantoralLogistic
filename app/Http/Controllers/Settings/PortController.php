<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StorePortRequest;
use App\Http\Requests\Settings\UpdatePortRequest;
use App\Models\Port;
use Inertia\Inertia;
use Inertia\Response;

class PortController extends Controller
{
    /**
     * Display a listing of the ports.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Port::class);

        return Inertia::render('settings/ports/index', [
            'ports' => Port::orderBy('code')->get(),
        ]);
    }

    /**
     * Store a newly created port in storage.
     */
    public function store(StorePortRequest $request)
    {
        $this->authorize('create', Port::class);

        Port::create($request->validated());

        return back()->with('success', 'Puerto creado correctamente.');
    }

    /**
     * Update the specified port in storage.
     */
    public function update(UpdatePortRequest $request, Port $port)
    {
        $this->authorize('update', $port);

        $port->update($request->validated());

        return back()->with('success', 'Puerto actualizado correctamente.');
    }

    /**
     * Remove the specified port from storage (soft delete).
     */
    public function destroy(Port $port)
    {
        $this->authorize('delete', $port);

        // Instead of hard delete, we toggle is_active or soft delete
        $port->delete();

        return back()->with('success', 'Puerto eliminado correctamente.');
    }
}
