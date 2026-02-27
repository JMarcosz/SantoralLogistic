<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarrierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('carriers/index', [
            'carriers' => Carrier::latest()->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:carriers,code',
            'is_active' => 'boolean',
        ]);

        Carrier::create($validated);

        return redirect()->back()->with('success', 'Carrier creado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Carrier $carrier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:carriers,code,' . $carrier->id,
            'is_active' => 'boolean',
        ]);

        $carrier->update($validated);

        return redirect()->back()->with('success', 'Carrier actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Carrier $carrier)
    {
        $carrier->delete();

        return redirect()->back()->with('success', 'Carrier eliminado exitosamente.');
    }
}
