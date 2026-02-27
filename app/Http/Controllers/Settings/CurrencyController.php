<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreCurrencyRequest;
use App\Http\Requests\Settings\UpdateCurrencyRequest;
use App\Models\Currency;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the currencies.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Currency::class);

        return Inertia::render('settings/currencies/index', [
            'currencies' => Currency::orderBy('code')->get(),
        ]);
    }

    /**
     * Store a newly created currency in storage.
     */
    public function store(StoreCurrencyRequest $request)
    {
        $this->authorize('create', Currency::class);

        $currency = Currency::create($request->validated());

        return back()->with('success', 'Moneda creada correctamente.');
    }

    /**
     * Update the specified currency in storage.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {
        $this->authorize('update', $currency);

        $currency->update($request->validated());

        return back()->with('success', 'Moneda actualizada correctamente.');
    }

    /**
     * Remove the specified currency from storage.
     */
    public function destroy(Currency $currency)
    {
        $this->authorize('delete', $currency);

        // Prevent deletion of default currency
        if ($currency->is_default) {
            return back()->with('error', 'No se puede eliminar la moneda por defecto.');
        }

        $currency->delete();

        return back()->with('success', 'Moneda eliminada correctamente.');
    }
}
