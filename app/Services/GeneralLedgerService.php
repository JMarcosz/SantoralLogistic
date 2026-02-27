<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DailyBalance;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for General Ledger (Libro Mayor) queries.
 * 
 * Uses daily_balances summary table for opening/closing balances
 * and joins with journal entries for detailed movements.
 * Falls back to direct calculation if daily_balances is empty.
 */
class GeneralLedgerService
{
    /**
     * Get the opening balance for an account before a specific date.
     * Falls back to direct calculation if daily_balances is empty.
     */
    public function getOpeningBalance(int $accountId, Carbon $date): array
    {
        $account = Account::find($accountId);
        if (!$account) {
            return ['balance' => 0, 'debit' => 0, 'credit' => 0];
        }

        // First try daily_balances
        $totals = DailyBalance::where('account_id', $accountId)
            ->where('date', '<', $date->toDateString())
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit = (float) ($totals->total_debit ?? 0);
        $totalCredit = (float) ($totals->total_credit ?? 0);

        // If no data in daily_balances, calculate from journal entries
        if ($totalDebit == 0 && $totalCredit == 0) {
            $directTotals = DB::table('journal_entry_lines as jel')
                ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
                ->where('jel.account_id', $accountId)
                ->where('je.status', 'posted')
                ->where('je.date', '<', $date->toDateString())
                ->selectRaw('SUM(jel.base_debit) as total_debit, SUM(jel.base_credit) as total_credit')
                ->first();

            $totalDebit = (float) ($directTotals->total_debit ?? 0);
            $totalCredit = (float) ($directTotals->total_credit ?? 0);
        }

        // Calculate balance based on account type
        $isDebitNormal = $account->normal_balance === Account::BALANCE_DEBIT;
        $balance = $isDebitNormal
            ? $totalDebit - $totalCredit
            : $totalCredit - $totalDebit;

        return [
            'balance' => $balance,
            'debit' => $totalDebit,
            'credit' => $totalCredit,
        ];
    }

    /**
     * Get movements for an account in a date range.
     * Returns paginated journal entry lines with entry details.
     */
    public function getMovements(
        int $accountId,
        Carbon $from,
        Carbon $to,
        int $perPage = 50
    ): LengthAwarePaginator {
        return JournalEntryLine::with(['journalEntry:id,entry_number,date,description,status'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($from, $to) {
                $query->where('status', 'posted')
                    ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
            })
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->select('journal_entry_lines.*')
            ->orderBy('journal_entries.date', 'asc')
            ->orderBy('journal_entries.id', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get movements with running balance calculated.
     */
    public function getMovementsWithRunningBalance(
        int $accountId,
        Carbon $from,
        Carbon $to,
        int $perPage = 50
    ): array {
        $account = Account::find($accountId);
        if (!$account) {
            return [
                'movements' => collect([]),
                'opening_balance' => 0,
            ];
        }

        $openingData = $this->getOpeningBalance($accountId, $from);
        $openingBalance = $openingData['balance'];
        $isDebitNormal = $account->normal_balance === Account::BALANCE_DEBIT;

        // Get paginated movements
        $movements = $this->getMovements($accountId, $from, $to, $perPage);

        // Calculate running balance for each movement
        $runningBalance = $openingBalance;
        $movementsWithBalance = [];

        // We need to calculate from the beginning of the page's data
        // For accurate running balance, we need the balance up to the current page
        $currentPage = $movements->currentPage();
        if ($currentPage > 1) {
            // Get sum of movements before this page
            $previousMovements = JournalEntryLine::where('account_id', $accountId)
                ->whereHas('journalEntry', function ($query) use ($from, $to) {
                    $query->where('status', 'posted')
                        ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
                })
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->orderBy('journal_entries.date', 'asc')
                ->orderBy('journal_entries.id', 'asc')
                ->take(($currentPage - 1) * $perPage)
                ->selectRaw('SUM(journal_entry_lines.base_debit) as total_debit, SUM(journal_entry_lines.base_credit) as total_credit')
                ->first();

            $prevDebit = (float) ($previousMovements->total_debit ?? 0);
            $prevCredit = (float) ($previousMovements->total_credit ?? 0);

            if ($isDebitNormal) {
                $runningBalance += $prevDebit - $prevCredit;
            } else {
                $runningBalance += $prevCredit - $prevDebit;
            }
        }

        foreach ($movements->items() as $movement) {
            $debit = (float) $movement->base_debit;
            $credit = (float) $movement->base_credit;

            if ($isDebitNormal) {
                $runningBalance += $debit - $credit;
            } else {
                $runningBalance += $credit - $debit;
            }

            $movementsWithBalance[] = [
                'id' => $movement->id,
                'date' => $movement->journalEntry->date,
                'entry_number' => $movement->journalEntry->entry_number,
                'entry_id' => $movement->journal_entry_id,
                'description' => $movement->description ?? $movement->journalEntry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }

        return [
            'movements' => $movementsWithBalance,
            'pagination' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
            'opening_balance' => $openingBalance,
        ];
    }

    /**
     * Get ledger summary with opening, movements, and closing balance.
     * Falls back to direct calculation if daily_balances is empty.
     */
    public function getLedgerSummary(int $accountId, Carbon $from, Carbon $to): array
    {
        $account = Account::find($accountId);
        if (!$account) {
            return [
                'account' => null,
                'opening_balance' => 0,
                'period_debit' => 0,
                'period_credit' => 0,
                'closing_balance' => 0,
            ];
        }

        $openingData = $this->getOpeningBalance($accountId, $from);
        $openingBalance = $openingData['balance'];

        // First try daily_balances for period totals
        $periodTotals = DailyBalance::where('account_id', $accountId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $periodDebit = (float) ($periodTotals->total_debit ?? 0);
        $periodCredit = (float) ($periodTotals->total_credit ?? 0);

        // If no data in daily_balances, calculate from journal entries directly
        if ($periodDebit == 0 && $periodCredit == 0) {
            $directTotals = DB::table('journal_entry_lines as jel')
                ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
                ->where('jel.account_id', $accountId)
                ->where('je.status', 'posted')
                ->whereBetween('je.date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('SUM(jel.base_debit) as total_debit, SUM(jel.base_credit) as total_credit')
                ->first();

            $periodDebit = (float) ($directTotals->total_debit ?? 0);
            $periodCredit = (float) ($directTotals->total_credit ?? 0);
        }

        // Calculate closing balance
        $isDebitNormal = $account->normal_balance === Account::BALANCE_DEBIT;
        $closingBalance = $openingBalance + ($isDebitNormal
            ? $periodDebit - $periodCredit
            : $periodCredit - $periodDebit);

        return [
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
            ],
            'opening_balance' => $openingBalance,
            'period_debit' => $periodDebit,
            'period_credit' => $periodCredit,
            'closing_balance' => $closingBalance,
        ];
    }

    /**
     * Export ledger movements to CSV.
     */
    public function exportToCsv(int $accountId, Carbon $from, Carbon $to): StreamedResponse
    {
        $account = Account::findOrFail($accountId);
        $summary = $this->getLedgerSummary($accountId, $from, $to);

        // Get all movements (not paginated)
        $movements = JournalEntryLine::with(['journalEntry:id,entry_number,date,description'])
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($from, $to) {
                $query->where('status', 'posted')
                    ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
            })
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->select('journal_entry_lines.*')
            ->orderBy('journal_entries.date', 'asc')
            ->orderBy('journal_entries.id', 'asc')
            ->get();

        $filename = "libro_mayor_{$account->code}_{$from->format('Y-m-d')}_{$to->format('Y-m-d')}.csv";

        return response()->stream(
            function () use ($account, $summary, $movements, $from, $to) {
                $handle = fopen('php://output', 'w');

                // UTF-8 BOM for Excel
                fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Header info
                fputcsv($handle, ['Libro Mayor - ' . $account->code . ' ' . $account->name]);
                fputcsv($handle, ['Período: ' . $from->format('d/m/Y') . ' al ' . $to->format('d/m/Y')]);
                fputcsv($handle, ['']);
                fputcsv($handle, ['Saldo Inicial:', number_format($summary['opening_balance'], 2)]);
                fputcsv($handle, ['']);

                // Column headers
                fputcsv($handle, ['Fecha', 'Asiento', 'Descripción', 'Débito', 'Crédito', 'Saldo']);

                // Calculate running balance
                $isDebitNormal = $account->normal_balance === Account::BALANCE_DEBIT;
                $runningBalance = $summary['opening_balance'];

                foreach ($movements as $movement) {
                    $debit = (float) $movement->base_debit;
                    $credit = (float) $movement->base_credit;

                    if ($isDebitNormal) {
                        $runningBalance += $debit - $credit;
                    } else {
                        $runningBalance += $credit - $debit;
                    }

                    fputcsv($handle, [
                        Carbon::parse($movement->journalEntry->date)->format('d/m/Y'),
                        $movement->journalEntry->entry_number,
                        $movement->description ?? $movement->journalEntry->description,
                        number_format($debit, 2),
                        number_format($credit, 2),
                        number_format($runningBalance, 2),
                    ]);
                }

                // Totals
                fputcsv($handle, ['']);
                fputcsv($handle, [
                    'TOTALES',
                    '',
                    '',
                    number_format($summary['period_debit'], 2),
                    number_format($summary['period_credit'], 2),
                    number_format($summary['closing_balance'], 2),
                ]);

                fclose($handle);
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }
}
