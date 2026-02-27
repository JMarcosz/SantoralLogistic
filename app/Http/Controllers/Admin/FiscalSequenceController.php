<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\FiscalSequenceOverlapException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFiscalSequenceRequest;
use App\Http\Requests\UpdateFiscalSequenceRequest;
use App\Models\FiscalSequence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FiscalSequenceController extends Controller
{
    /**
     * NCF types catalog with labels
     */
    protected array $ncfTypes = [
        'B01' => 'Crédito Fiscal',
        'B02' => 'Consumidor Final',
        'B14' => 'Regímenes Especiales',
        'B15' => 'Gubernamental',
        'B16' => 'Exportaciones',
    ];

    /**
     * Display a listing of fiscal sequences.
     */
    public function index(Request $request): Response
    {
        $this->authorize('manage-fiscal-sequences');

        $sequences = FiscalSequence::query()
            ->when($request->ncf_type, function ($query, $ncfType) {
                $query->where('ncf_type', $ncfType);
            })
            ->when($request->series !== null, function ($query) use ($request) {
                if ($request->series === '') {
                    $query->whereNull('series');
                } else {
                    $query->where('series', $request->series);
                }
            })
            ->when($request->is_active !== null, function ($query) use ($request) {
                $query->where('is_active', $request->is_active === '1' || $request->is_active === true);
            })
            ->orderByDesc('valid_from')
            ->orderBy('ncf_type')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($sequence) {
                return [
                    'id' => $sequence->id,
                    'ncf_type' => $sequence->ncf_type,
                    'series' => $sequence->series,
                    'ncf_from' => $sequence->ncf_from,
                    'ncf_to' => $sequence->ncf_to,
                    'current_ncf' => $sequence->current_ncf,
                    'valid_from' => $sequence->valid_from->format('Y-m-d'),
                    'valid_to' => $sequence->valid_to->format('Y-m-d'),
                    'is_active' => $sequence->is_active,
                    'usage_percent' => $this->calculateUsagePercent($sequence),
                    'is_exhausted' => $sequence->isExhausted(),
                    'is_valid_now' => $sequence->isValidNow(),
                    'near_exhaustion' => $sequence->isNearExhaustion(80), // 80% threshold
                    'near_expiration' => $sequence->isNearExpiration(15), // 15 days threshold
                    'days_until_expiration' => (int) now()->diffInDays($sequence->valid_to, false),
                ];
            });

        return Inertia::render('Admin/FiscalSequences/Index', [
            'sequences' => $sequences,
            'ncfTypes' => $this->ncfTypes,
            'filters' => $request->only(['ncf_type', 'series', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new fiscal sequence.
     */
    public function create(): Response
    {
        $this->authorize('manage-fiscal-sequences');

        return Inertia::render('Admin/FiscalSequences/Form', [
            'ncfTypes' => $this->ncfTypes,
            'sequence' => null,
        ]);
    }

    /**
     * Store a newly created fiscal sequence.
     */
    public function store(StoreFiscalSequenceRequest $request): RedirectResponse
    {
        $this->authorize('manage-fiscal-sequences');

        $sequence = FiscalSequence::create($request->validated());

        return redirect()
            ->route('admin.fiscal-sequences.index')
            ->with('success', 'Rango NCF creado exitosamente.');
    }

    /**
     * Show the form for editing the specified fiscal sequence.
     */
    public function edit(FiscalSequence $fiscalSequence): Response
    {
        $this->authorize('manage-fiscal-sequences');

        return Inertia::render('Admin/FiscalSequences/Form', [
            'ncfTypes' => $this->ncfTypes,
            'sequence' => [
                'id' => $fiscalSequence->id,
                'ncf_type' => $fiscalSequence->ncf_type,
                'series' => $fiscalSequence->series,
                'ncf_from' => $fiscalSequence->ncf_from,
                'ncf_to' => $fiscalSequence->ncf_to,
                'current_ncf' => $fiscalSequence->current_ncf,
                'valid_from' => $fiscalSequence->valid_from->format('Y-m-d'),
                'valid_to' => $fiscalSequence->valid_to->format('Y-m-d'),
                'is_active' => $fiscalSequence->is_active,
            ],
        ]);
    }

    /**
     * Update the specified fiscal sequence.
     */
    public function update(UpdateFiscalSequenceRequest $request, FiscalSequence $fiscalSequence): RedirectResponse
    {
        $this->authorize('manage-fiscal-sequences');

        $fiscalSequence->update($request->validated());

        return redirect()
            ->route('admin.fiscal-sequences.index')
            ->with('success', 'Rango NCF actualizado exitosamente.');
    }

    /**
     * Soft-delete fiscal sequence by deactivating it.
     */
    public function destroy(FiscalSequence $fiscalSequence): RedirectResponse
    {
        $this->authorize('manage-fiscal-sequences');

        $fiscalSequence->update(['is_active' => false]);

        return redirect()
            ->route('admin.fiscal-sequences.index')
            ->with('success', 'Rango NCF desactivado exitosamente.');
    }

    /**
     * Calculate usage percentage for a fiscal sequence.
     */
    protected function calculateUsagePercent(FiscalSequence $sequence): ?float
    {
        if ($sequence->current_ncf === null) {
            return 0.0;
        }

        // Extract numeric parts for calculation
        preg_match('/(\d+)$/', $sequence->ncf_from, $fromMatches);
        preg_match('/(\d+)$/', $sequence->ncf_to, $toMatches);
        preg_match('/(\d+)$/', $sequence->current_ncf, $currentMatches);

        if (!$fromMatches || !$toMatches || !$currentMatches) {
            return null;
        }

        $from = (int) $fromMatches[1];
        $to = (int) $toMatches[1];
        $current = (int) $currentMatches[1];

        $total = $to - $from;
        if ($total <= 0) {
            return null;
        }

        $used = $current - $from;
        return round(($used / $total) * 100, 2);
    }
}
