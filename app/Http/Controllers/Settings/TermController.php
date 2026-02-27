<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermController extends Controller
{
    /**
     * Display a listing of terms.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Term::class);

        $query = Term::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $terms = $query->orderBy('type')->orderBy('name')->get();

        return Inertia::render('settings/terms/index', [
            'terms' => $terms,
            'types' => collect(Term::TYPES)->map(fn($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'filters' => [
                'type' => $request->type,
            ],
        ]);
    }

    /**
     * Store a newly created term.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Term::class);

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'body' => 'required|string',
            'type' => 'required|string|in:' . implode(',', array_keys(Term::TYPES)),
            'scope' => 'nullable|string|max:50',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If setting as default, unset other defaults of same type
        if ($validated['is_default'] ?? false) {
            Term::where('type', $validated['type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        Term::create($validated);

        return back()->with('success', 'Término creado correctamente.');
    }

    /**
     * Update the specified term.
     */
    public function update(Request $request, Term $term): RedirectResponse
    {
        $this->authorize('update', $term);

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'body' => 'required|string',
            'type' => 'required|string|in:' . implode(',', array_keys(Term::TYPES)),
            'scope' => 'nullable|string|max:50',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If setting as default, unset other defaults of same type
        if (($validated['is_default'] ?? false) && !$term->is_default) {
            Term::where('type', $validated['type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $term->update($validated);

        return back()->with('success', 'Término actualizado correctamente.');
    }

    /**
     * Remove the specified term.
     */
    public function destroy(Term $term): RedirectResponse
    {
        $this->authorize('delete', $term);

        // Check if term is in use
        $inUseQuotes = \App\Models\Quote::where('payment_terms_id', $term->id)
            ->orWhere('footer_terms_id', $term->id)
            ->exists();

        $inUseSO = \App\Models\ShippingOrder::where('footer_terms_id', $term->id)->exists();

        if ($inUseQuotes || $inUseSO) {
            return back()->with('error', 'Este término está en uso y no puede ser eliminado. Puede desactivarlo en su lugar.');
        }

        $term->delete();

        return back()->with('success', 'Término eliminado correctamente.');
    }
}
