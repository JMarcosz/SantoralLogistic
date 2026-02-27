<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Service for bank reconciliation operations.
 */
class BankReconciliationService
{
    /**
     * Find potential matches for a bank statement line.
     * 
     * Searches journal entry lines and payments by:
     * - Amount match (exact or close)
     * - Date range (within 7 days)
     * - Reference match (if available)
     */
    public function findPotentialMatches(BankStatementLine $line, int $limit = 10): Collection
    {
        $statement = $line->bankStatement;
        $accountId = $statement->account_id;
        $amount = abs($line->amount);
        $date = $line->transaction_date;
        $isDebit = $line->amount < 0;

        // Search journal entry lines for this account
        $journalMatches = JournalEntryLine::query()
            ->with(['journalEntry'])
            ->where('account_id', $accountId)
            ->where('is_reconciled', false)
            // Match the direction (bank debit = GL credit, bank credit = GL debit)
            ->when($isDebit, function ($q) use ($amount) {
                // Bank debit (withdrawal) = credit in GL
                $q->where('credit', '>', 0)
                    ->whereBetween('credit', [$amount * 0.99, $amount * 1.01]);
            })
            ->when(!$isDebit, function ($q) use ($amount) {
                // Bank credit (deposit) = debit in GL
                $q->where('debit', '>', 0)
                    ->whereBetween('debit', [$amount * 0.99, $amount * 1.01]);
            })
            // Date range: within 7 days
            ->whereHas('journalEntry', function ($q) use ($date) {
                $q->whereBetween('date', [
                    $date->copy()->subDays(7),
                    $date->copy()->addDays(7),
                ]);
            })
            ->orderByRaw("ABS(DATEDIFF(journal_entries.date, ?))", [$date])
            ->limit($limit)
            ->get()
            ->map(fn($jel) => [
                'type' => 'journal_entry_line',
                'id' => $jel->id,
                'date' => $jel->journalEntry->date,
                'description' => $jel->description ?? $jel->journalEntry->description,
                'amount' => $isDebit ? $jel->credit : $jel->debit,
                'reference' => $jel->journalEntry->entry_number,
                'match_score' => $this->calculateMatchScore($line, $jel),
            ]);

        // Also search payments if this is a bank account that receives payments
        $paymentMatches = Payment::query()
            ->where('bank_account_id', $accountId)
            ->whereIn('status', ['posted', 'approved'])
            ->when($line->reference, function ($q, $ref) {
                $q->where('reference', 'like', "%{$ref}%");
            })
            ->whereBetween('payment_date', [
                $date->copy()->subDays(7),
                $date->copy()->addDays(7),
            ])
            ->whereBetween('amount', [$amount * 0.99, $amount * 1.01])
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'type' => 'payment',
                'id' => $p->id,
                'date' => $p->payment_date,
                'description' => "Pago {$p->payment_number}",
                'amount' => $p->amount,
                'reference' => $p->reference ?? $p->payment_number,
                'match_score' => 80, // Base score for payments
            ]);

        return $journalMatches->merge($paymentMatches)
            ->sortByDesc('match_score')
            ->take($limit)
            ->values();
    }

    /**
     * Calculate match score (0-100) between statement line and journal entry line.
     */
    protected function calculateMatchScore(BankStatementLine $statementLine, JournalEntryLine $journalLine): int
    {
        $score = 0;

        // Amount match (max 40 points)
        $statementAmount = abs($statementLine->amount);
        $journalAmount = $statementLine->is_debit ? $journalLine->credit : $journalLine->debit;
        $amountDiff = abs($statementAmount - $journalAmount) / max($statementAmount, 0.01);
        $score += max(0, 40 - ($amountDiff * 100));

        // Date match (max 30 points)
        $daysDiff = abs($statementLine->transaction_date->diffInDays($journalLine->journalEntry->date));
        $score += max(0, 30 - ($daysDiff * 5));

        // Reference match (max 30 points)
        if ($statementLine->reference && $journalLine->description) {
            similar_text(
                strtolower($statementLine->reference),
                strtolower($journalLine->description),
                $similarity
            );
            $score += ($similarity / 100) * 30;
        }

        return (int) min(100, $score);
    }

    /**
     * Reconcile a statement line with a journal entry line.
     */
    public function reconcileWithJournalLine(
        BankStatementLine $statementLine,
        JournalEntryLine $journalLine,
        ?string $notes = null
    ): void {
        $statementLine->matchToJournalLine($journalLine, Auth::id(), $notes);
    }

    /**
     * Reconcile a statement line with a payment.
     */
    public function reconcileWithPayment(
        BankStatementLine $statementLine,
        Payment $payment,
        ?string $notes = null
    ): void {
        $statementLine->matchToPayment($payment, Auth::id(), $notes);
    }

    /**
     * Unreconcile a statement line.
     */
    public function unreconcile(BankStatementLine $statementLine): void
    {
        $statementLine->unmatch();
    }

    /**
     * Get unreconciled items report for an account.
     */
    public function getUnreconciledItems(Account $account, ?string $fromDate = null, ?string $toDate = null): array
    {
        // Unreconciled journal entry lines
        $journalLines = JournalEntryLine::query()
            ->with(['journalEntry'])
            ->where('account_id', $account->id)
            ->where('is_reconciled', false)
            ->whereHas('journalEntry', function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'posted');
                if ($fromDate) {
                    $q->where('date', '>=', $fromDate);
                }
                if ($toDate) {
                    $q->where('date', '<=', $toDate);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Unreconciled bank statement lines
        $statementLines = BankStatementLine::query()
            ->with(['bankStatement'])
            ->whereHas('bankStatement', function ($q) use ($account) {
                $q->where('account_id', $account->id);
            })
            ->where('is_reconciled', false)
            ->when($fromDate, fn($q) => $q->where('transaction_date', '>=', $fromDate))
            ->when($toDate, fn($q) => $q->where('transaction_date', '<=', $toDate))
            ->orderBy('transaction_date', 'desc')
            ->get();

        return [
            'journal_lines' => $journalLines,
            'statement_lines' => $statementLines,
            'journal_total' => $journalLines->sum(fn($l) => $l->debit - $l->credit),
            'statement_total' => $statementLines->sum('amount'),
        ];
    }

    /**
     * Import statement lines from CSV data.
     */
    public function importFromCsv(BankStatement $statement, array $rows): int
    {
        $imported = 0;

        foreach ($rows as $row) {
            // Expected format: date, description, reference, amount
            BankStatementLine::create([
                'bank_statement_id' => $statement->id,
                'transaction_date' => $row['date'] ?? $row['transaction_date'] ?? now(),
                'value_date' => $row['value_date'] ?? null,
                'reference' => $row['reference'] ?? $row['ref'] ?? null,
                'description' => $row['description'] ?? $row['desc'] ?? null,
                'amount' => (float) ($row['amount'] ?? 0),
                'running_balance' => isset($row['balance']) ? (float) $row['balance'] : null,
                'transaction_type' => $row['type'] ?? $row['transaction_type'] ?? null,
            ]);
            $imported++;
        }

        $statement->recalculateTotals();

        if ($statement->status === BankStatement::STATUS_DRAFT) {
            $statement->update(['status' => BankStatement::STATUS_IN_PROGRESS]);
        }

        return $imported;
    }
}
