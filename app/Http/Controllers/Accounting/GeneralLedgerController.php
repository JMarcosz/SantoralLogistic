<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\GeneralLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneralLedgerController extends Controller
{
    public function __construct(
        protected GeneralLedgerService $ledgerService
    ) {}

    /**
     * Display the general ledger page.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Account::class);

        // Get filter parameters
        $accountId = $request->input('account_id');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        // Get accounts for selector
        $accounts = Account::active()
            ->postable()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'normal_balance']);

        // Initialize data
        $summary = null;
        $movements = [];
        $pagination = null;
        $openingBalance = 0;

        // If account is selected, get ledger data
        if ($accountId) {
            $fromDate = Carbon::parse($from);
            $toDate = Carbon::parse($to);

            $summary = $this->ledgerService->getLedgerSummary(
                (int) $accountId,
                $fromDate,
                $toDate
            );

            $movementsData = $this->ledgerService->getMovementsWithRunningBalance(
                (int) $accountId,
                $fromDate,
                $toDate,
                50
            );

            $movements = $movementsData['movements'];
            $pagination = $movementsData['pagination'];
            $openingBalance = $movementsData['opening_balance'];
        }

        return Inertia::render('accounting/ledger/index', [
            'accounts' => $accounts,
            'filters' => [
                'account_id' => $accountId,
                'from' => $from,
                'to' => $to,
            ],
            'summary' => $summary,
            'movements' => $movements,
            'openingBalance' => $openingBalance,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Export ledger to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Account::class);

        $request->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $accountId = (int) $request->input('account_id');
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));

        return $this->ledgerService->exportToCsv($accountId, $from, $to);
    }
}
