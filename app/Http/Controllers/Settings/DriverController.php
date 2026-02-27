<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Driver::class);

        $query = Driver::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Active filter
        if ($request->filled('is_active') && $request->input('is_active') !== 'all') {
            $isActive = $request->input('is_active') === 'active';
            $query->where('is_active', $isActive);
        }

        $drivers = $query->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('settings/drivers/index', [
            'drivers' => $drivers,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Driver::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:255',
            'vehicle_plate' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        Driver::create($validated);

        return redirect()->back()->with('success', 'Conductor creado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:255',
            'vehicle_plate' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $driver->update($validated);

        return redirect()->back()->with('success', 'Conductor actualizado correctamente.');
    }

    /**
     * Toggle the active status of the driver.
     */
    public function toggleActive(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);

        $driver->update(['is_active' => !$driver->is_active]);

        return redirect()->back()->with('success', 'Estado del conductor actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver): RedirectResponse
    {
        $this->authorize('delete', $driver);

        $driver->delete();

        return redirect()->back()->with('success', 'Conductor eliminado correctamente.');
    }
}
