<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DailyBalance;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for maintaining daily balance summary table.
 * 
 * Called when journal entries are posted or reversed to update
 * the pre-calculated daily balances for affected accounts.
 */
class DailyBalanceService
{
    /**
     * Update daily balances for all accounts affected by a journal entry.
     * Called when an entry is posted or reversed.
     */
    public function updateBalancesForEntry(JournalEntry $entry): void
    {
        if ($entry->status !== 'posted') {
            return;
        }

        $entryDate = Carbon::parse($entry->date)->toDateString();

        // Group lines by account
        $accountTotals = $entry->lines
            ->groupBy('account_id')
            ->map(function ($lines) {
                return [
                    'debit' => $lines->sum('base_debit'),
                    'credit' => $lines->sum('base_credit'),
                    'count' => 1,
                ];
            });

        foreach ($accountTotals as $accountId => $totals) {
            $this->upsertDailyBalance(
                $accountId,
                $entryDate,
                $totals['debit'],
                $totals['credit'],
                $totals['count']
            );
        }

        // Recalculate running balances for affected accounts
        foreach ($accountTotals->keys() as $accountId) {
            $this->recalculateRunningBalances($accountId, $entryDate);
        }
    }

    /**
     * Subtract balances when an entry is reversed.
     */
    public function subtractBalancesForEntry(JournalEntry $entry): void
    {
        $entryDate = Carbon::parse($entry->date)->toDateString();

        $accountTotals = $entry->lines
            ->groupBy('account_id')
            ->map(function ($lines) {
                return [
                    'debit' => $lines->sum('base_debit'),
                    'credit' => $lines->sum('base_credit'),
                ];
            });

        foreach ($accountTotals as $accountId => $totals) {
            $dailyBalance = DailyBalance::where('account_id', $accountId)
                ->where('date', $entryDate)
                ->first();

            if ($dailyBalance) {
                $dailyBalance->update([
                    'debit' => max(0, $dailyBalance->debit - $totals['debit']),
                    'credit' => max(0, $dailyBalance->credit - $totals['credit']),
                    'entry_count' => max(0, $dailyBalance->entry_count - 1),
                ]);
            }
        }

        // Recalculate running balances
        foreach ($accountTotals->keys() as $accountId) {
            $this->recalculateRunningBalances($accountId, $entryDate);
        }
    }

    /**
     * Upsert a daily balance record (create or update).
     */
    protected function upsertDailyBalance(
        int $accountId,
        string $date,
        float $debit,
        float $credit,
        int $entryCount
    ): void {
        $existing = DailyBalance::where('account_id', $accountId)
            ->where('date', $date)
            ->first();

        if ($existing) {
            $existing->update([
                'debit' => $existing->debit + $debit,
                'credit' => $existing->credit + $credit,
                'entry_count' => $existing->entry_count + $entryCount,
            ]);
        } else {
            DailyBalance::create([
                'account_id' => $accountId,
                'date' => $date,
                'debit' => $debit,
                'credit' => $credit,
                'entry_count' => $entryCount,
                'balance' => 0, // Will be recalculated
            ]);
        }
    }

    /**
     * Recalculate running balances for an account from a specific date forward.
     */
    public function recalculateRunningBalances(int $accountId, ?string $fromDate = null): void
    {
        $account = Account::find($accountId);
        if (!$account) {
            return;
        }

        $isDebitNormal = $account->normal_balance === Account::BALANCE_DEBIT;

        // Get all daily balances for this account, ordered by date
        $query = DailyBalance::where('account_id', $accountId)
            ->orderBy('date');

        if ($fromDate) {
            // Get the balance before this date
            $previousBalance = DailyBalance::where('account_id', $accountId)
                ->where('date', '<', $fromDate)
                ->orderBy('date', 'desc')
                ->value('balance') ?? 0;

            $query->where('date', '>=', $fromDate);
        } else {
            $previousBalance = 0;
        }

        $runningBalance = (float) $previousBalance;

        foreach ($query->get() as $dailyBalance) {
            // Calculate new balance based on account type
            if ($isDebitNormal) {
                $runningBalance += (float) $dailyBalance->debit - (float) $dailyBalance->credit;
            } else {
                $runningBalance += (float) $dailyBalance->credit - (float) $dailyBalance->debit;
            }

            $dailyBalance->update(['balance' => $runningBalance]);
        }
    }

    /**
     * Rebuild all daily balances from scratch for an account.
     * Useful for data repair or migration.
     */
    public function rebuildForAccount(int $accountId): void
    {
        $account = Account::findOrFail($accountId);

        // Delete existing daily balances
        DailyBalance::where('account_id', $accountId)->delete();

        // Get all posted journal entry lines for this account
        $movements = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('jel.account_id', $accountId)
            ->where('je.status', 'posted')
            ->select(
                DB::raw('DATE(je.date) as entry_date'),
                DB::raw('SUM(jel.base_debit) as total_debit'),
                DB::raw('SUM(jel.base_credit) as total_credit'),
                DB::raw('COUNT(DISTINCT je.id) as entry_count')
            )
            ->groupBy('entry_date')
            ->orderBy('entry_date')
            ->get();

        $isDebitNormal = $account->normal_balance === Account::BALANCE_DEBIT;
        $runningBalance = 0;

        foreach ($movements as $day) {
            if ($isDebitNormal) {
                $runningBalance += (float) $day->total_debit - (float) $day->total_credit;
            } else {
                $runningBalance += (float) $day->total_credit - (float) $day->total_debit;
            }

            DailyBalance::create([
                'account_id' => $accountId,
                'date' => $day->entry_date,
                'debit' => $day->total_debit,
                'credit' => $day->total_credit,
                'balance' => $runningBalance,
                'entry_count' => $day->entry_count,
            ]);
        }
    }

    /**
     * Rebuild daily balances for all accounts.
     */
    public function rebuildAll(): void
    {
        $accountIds = Account::postable()->pluck('id');

        foreach ($accountIds as $accountId) {
            $this->rebuildForAccount($accountId);
        }
    }
}
