<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreTransportModeRequest;
use App\Http\Requests\Settings\UpdateTransportModeRequest;
use App\Models\TransportMode;
use Inertia\Inertia;
use Inertia\Response;

class TransportModeController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TransportMode::class);

        return Inertia::render('settings/transport-modes/index', [
            'transportModes' => TransportMode::orderBy('code')->get(),
        ]);
    }

    public function store(StoreTransportModeRequest $request)
    {
        $this->authorize('create', TransportMode::class);

        TransportMode::create($request->validated());

        return back()->with('success', 'Modo de transporte creado correctamente.');
    }

    public function update(UpdateTransportModeRequest $request, TransportMode $transportMode)
    {
        $this->authorize('update', $transportMode);

        $transportMode->update($request->validated());

        return back()->with('success', 'Modo de transporte actualizado correctamente.');
    }

    public function destroy(TransportMode $transportMode)
    {
        $this->authorize('delete', $transportMode);

        $transportMode->delete();

        return back()->with('success', 'Modo de transporte eliminado correctamente.');
    }
}
