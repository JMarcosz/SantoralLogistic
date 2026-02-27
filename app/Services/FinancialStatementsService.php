<?php

namespace App\Services;

use App\Enums\JournalEntryStatus;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for generating financial statements.
 * 
 * Generates Balance Sheet (BS) and Income Statement (P&L) reports
 * based on posted journal entries.
 */
class FinancialStatementsService
{
    /**
     * Generate Balance Sheet report.
     * 
     * Assets = Liabilities + Equity
     * 
     * @param string $asOfDate Date for the balance sheet (YYYY-MM-DD)
     * @param bool $includeZeroBalances Whether to show accounts with zero balance
     */
    public function getBalanceSheet(string $asOfDate, bool $includeZeroBalances = false): array
    {
        $date = Carbon::parse($asOfDate)->endOfDay();

        // Get all balance sheet accounts (assets, liabilities, equity)
        $accounts = Account::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Calculate balance for each account as of the date
        $balances = $this->calculateAccountBalances($accounts->pluck('id'), $date);

        // Group accounts by type
        $assets = $this->buildAccountTree(
            $accounts->where('type', 'asset'),
            $balances,
            $includeZeroBalances
        );

        $liabilities = $this->buildAccountTree(
            $accounts->where('type', 'liability'),
            $balances,
            $includeZeroBalances
        );

        $equity = $this->buildAccountTree(
            $accounts->where('type', 'equity'),
            $balances,
            $includeZeroBalances
        );

        // Calculate retained earnings (net income from prior periods + current period)
        $retainedEarnings = $this->calculateRetainedEarnings($date);

        // Calculate totals
        $totalAssets = $this->sumTree($assets);
        $totalLiabilities = $this->sumTree($liabilities);
        $totalEquity = $this->sumTree($equity) + $retainedEarnings;

        return [
            'as_of_date' => $date->toDateString(),
            'assets' => [
                'accounts' => $assets,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity,
            ],
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    /**
     * Generate Income Statement (P&L) report.
     * 
     * Revenue - Expenses = Net Income
     * 
     * @param string $period Period in YYYY-MM format
     * @param bool $ytd Whether to show Year-to-Date cumulative
     */
    public function getIncomeStatement(string $period, bool $ytd = false): array
    {
        $periodDate = Carbon::parse($period . '-01');

        if ($ytd) {
            $startDate = $periodDate->copy()->startOfYear();
        } else {
            $startDate = $periodDate->copy()->startOfMonth();
        }
        $endDate = $periodDate->copy()->endOfMonth();

        // Get all P&L accounts (revenue, expense)
        $accounts = Account::whereIn('type', ['revenue', 'expense'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Calculate period movements for each account
        $movements = $this->calculatePeriodMovements($accounts->pluck('id'), $startDate, $endDate);

        // Group accounts by type
        $revenue = $this->buildAccountTree(
            $accounts->where('type', 'revenue'),
            $movements,
            false,
            true // use credit minus debit for revenue
        );

        $expenses = $this->buildAccountTree(
            $accounts->where('type', 'expense'),
            $movements,
            false,
            false // use debit minus credit for expenses
        );

        // Calculate totals
        $totalRevenue = $this->sumTree($revenue);
        $totalExpenses = $this->sumTree($expenses);
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'period' => $period,
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'is_ytd' => $ytd,
            'revenue' => [
                'accounts' => $revenue,
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $expenses,
                'total' => $totalExpenses,
            ],
            'net_income' => $netIncome,
            'gross_margin' => $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0,
        ];
    }

    /**
     * Calculate account balances as of a specific date.
     */
    protected function calculateAccountBalances(Collection $accountIds, Carbon $asOfDate): array
    {
        $balances = [];

        // Get sum of all posted entries up to the date
        $results = JournalEntryLine::query()
            ->select('account_id')
            ->selectRaw('SUM(base_debit) as total_debit')
            ->selectRaw('SUM(base_credit) as total_credit')
            ->whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('status', JournalEntryStatus::Posted)
                    ->where('date', '<=', $asOfDate);
            })
            ->groupBy('account_id')
            ->get();

        foreach ($results as $row) {
            $balances[$row->account_id] = [
                'debit' => (float) $row->total_debit,
                'credit' => (float) $row->total_credit,
            ];
        }

        return $balances;
    }

    /**
     * Calculate period movements for P&L accounts.
     */
    protected function calculatePeriodMovements(Collection $accountIds, Carbon $startDate, Carbon $endDate): array
    {
        $movements = [];

        $results = JournalEntryLine::query()
            ->select('account_id')
            ->selectRaw('SUM(base_debit) as total_debit')
            ->selectRaw('SUM(base_credit) as total_credit')
            ->whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', JournalEntryStatus::Posted)
                    ->whereBetween('date', [$startDate, $endDate]);
            })
            ->groupBy('account_id')
            ->get();

        foreach ($results as $row) {
            $movements[$row->account_id] = [
                'debit' => (float) $row->total_debit,
                'credit' => (float) $row->total_credit,
            ];
        }

        return $movements;
    }

    /**
     * Calculate retained earnings (cumulative P&L from inception to date).
     */
    protected function calculateRetainedEarnings(Carbon $asOfDate): float
    {
        // Sum of all revenue minus expenses up to asOfDate
        $revenue = JournalEntryLine::query()
            ->whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('status', JournalEntryStatus::Posted)
                    ->where('date', '<=', $asOfDate);
            })
            ->selectRaw('SUM(base_credit) - SUM(base_debit) as net')
            ->value('net') ?? 0;

        $expenses = JournalEntryLine::query()
            ->whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('status', JournalEntryStatus::Posted)
                    ->where('date', '<=', $asOfDate);
            })
            ->selectRaw('SUM(base_debit) - SUM(base_credit) as net')
            ->value('net') ?? 0;

        return (float) $revenue - (float) $expenses;
    }

    /**
     * Build hierarchical account tree with balances.
     */
    protected function buildAccountTree(
        Collection $accounts,
        array $balances,
        bool $includeZeroBalances = false,
        bool $creditPositive = false
    ): array {
        $tree = [];

        foreach ($accounts as $account) {
            $debit = $balances[$account->id]['debit'] ?? 0;
            $credit = $balances[$account->id]['credit'] ?? 0;

            // Calculate balance based on account nature
            $balance = $creditPositive
                ? $credit - $debit  // Revenue accounts
                : $debit - $credit; // Asset/Expense accounts

            // For liability/equity, credit increases balance
            if (in_array($account->type, ['liability', 'equity'])) {
                $balance = $credit - $debit;
            }

            if (!$includeZeroBalances && abs($balance) < 0.01) {
                continue;
            }

            $tree[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => round($balance, 2),
                'level' => $account->level,
                'is_parent' => $account->is_parent,
            ];
        }

        return $tree;
    }

    /**
     * Sum all balances in tree.
     */
    protected function sumTree(array $tree): float
    {
        return array_sum(array_column($tree, 'balance'));
    }

    /**
     * Compare two periods for variance analysis.
     */
    public function compareIncomeStatements(string $period1, string $period2): array
    {
        $is1 = $this->getIncomeStatement($period1);
        $is2 = $this->getIncomeStatement($period2);

        return [
            'period_1' => $is1,
            'period_2' => $is2,
            'revenue_variance' => $is1['revenue']['total'] - $is2['revenue']['total'],
            'expense_variance' => $is1['expenses']['total'] - $is2['expenses']['total'],
            'net_income_variance' => $is1['net_income'] - $is2['net_income'],
        ];
    }
}
