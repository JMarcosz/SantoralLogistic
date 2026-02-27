<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRateRequest;
use App\Http\Requests\Settings\UpdateRateRequest;
use App\Models\Currency;
use App\Models\Port;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\TransportMode;
use Inertia\Inertia;
use Inertia\Response;

class RateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Rate::class);

        return Inertia::render('settings/rates/index', [
            'rates' => Rate::with([
                'originPort:id,code,name',
                'destinationPort:id,code,name',
                'transportMode:id,code,name',
                'serviceType:id,code,name',
                'currency:id,code,symbol',
            ])->orderBy('origin_port_id')->orderBy('destination_port_id')->get(),
            'ports' => Port::active()->orderBy('code')->get(['id', 'code', 'name', 'type']),
            'transportModes' => TransportMode::active()->orderBy('code')->get(['id', 'code', 'name']),
            'serviceTypes' => ServiceType::active()->orderBy('code')->get(['id', 'code', 'name']),
            'currencies' => Currency::active()->orderBy('code')->get(['id', 'code', 'name', 'symbol']),
        ]);
    }

    public function store(StoreRateRequest $request)
    {
        $this->authorize('create', Rate::class);

        Rate::create($request->validated());

        return back()->with('success', 'Tarifa creada correctamente.');
    }

    public function update(UpdateRateRequest $request, Rate $rate)
    {
        $this->authorize('update', $rate);

        $rate->update($request->validated());

        return back()->with('success', 'Tarifa actualizada correctamente.');
    }

    public function destroy(Rate $rate)
    {
        $this->authorize('delete', $rate);

        $rate->delete();

        return back()->with('success', 'Tarifa eliminada correctamente.');
    }
}
