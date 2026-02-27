<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Services\AccountingPeriodService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AccountingPeriodController
 * 
 * Manages accounting period listing, close/reopen operations.
 */
class AccountingPeriodController extends Controller
{
    public function __construct(
        protected AccountingPeriodService $periodService,
        protected AuditService $auditService
    ) {}

    /**
     * Display listing of accounting periods.
     */
    public function index(Request $request): Response
    {
        $year = $request->input('year', now()->year);

        $periods = AccountingPeriod::where('year', $year)
            ->with(['closer', 'reopener'])
            ->orderBy('month')
            ->get();

        // Ensure all 12 months exist
        if ($periods->count() < 12) {
            $this->periodService->initializeYear($year);
            $periods = AccountingPeriod::where('year', $year)
                ->with(['closer', 'reopener'])
                ->orderBy('month')
                ->get();
        }

        return Inertia::render('accounting/periods/index', [
            'periods' => $periods,
            'currentYear' => $year,
            'canManage' => $request->user()->can('accounting.manage'),
            'canClosePeriod' => $request->user()->can('accounting.close_period'),
        ]);
    }

    /**
     * Close an accounting period with validation.
     */
    public function close(Request $request, AccountingPeriod $period): RedirectResponse
    {
        $force = $request->boolean('force', false);

        try {
            $closedPeriod = $this->periodService->closeWithValidation($period, $force);

            $this->auditService->logPeriodClosed($closedPeriod);

            return back()->with('success', "Período {$closedPeriod->display_name} cerrado exitosamente");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get period close preview/summary.
     */
    public function closePreview(AccountingPeriod $period): \Illuminate\Http\JsonResponse
    {
        $summary = $this->periodService->getPeriodCloseSummary($period);

        return response()->json($summary);
    }

    /**
     * Reopen a closed accounting period.
     */
    public function reopen(AccountingPeriod $period): RedirectResponse
    {
        try {
            $reopenedPeriod = $this->periodService->reopen($period);

            $this->auditService->logPeriodReopened($reopenedPeriod);

            return back()->with('success', "Período {$reopenedPeriod->display_name} reabierto exitosamente");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Initialize periods for a year.
     */
    public function initializeYear(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $created = $this->periodService->initializeYear($request->year);

        return response()->json([
            'success' => true,
            'message' => "{$created} períodos creados para el año {$request->year}",
            'created' => $created,
        ]);
    }
}
