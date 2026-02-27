<?php

namespace App\Http\Controllers;

use App\Models\Division;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DivisionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('divisions/index', [
            'divisions' => Division::latest()->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:divisions,code',
            'is_active' => 'boolean',
        ]);

        Division::create($validated);

        return redirect()->back()->with('success', 'División creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:divisions,code,' . $division->id,
            'is_active' => 'boolean',
        ]);

        $division->update($validated);

        return redirect()->back()->with('success', 'División actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Division $division)
    {
        $division->delete();

        return redirect()->back()->with('success', 'División eliminada exitosamente.');
    }
}
