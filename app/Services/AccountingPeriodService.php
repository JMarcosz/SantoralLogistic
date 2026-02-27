<?php

namespace App\Services;

use App\Enums\JournalEntryStatus;
use App\Exceptions\Accounting\PeriodClosedException;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AccountingPeriodService
 * 
 * Handles business logic for accounting periods: validation, close/reopen operations.
 */
class AccountingPeriodService
{
    /**
     * Assert that a given date falls within an open period.
     * 
     * Throws exception if period is closed or date is before lock_date.
     * 
     * @param Carbon|string $date
     * @throws PeriodClosedException
     * @return AccountingPeriod
     */
    public function assertOpen(Carbon|string $date): AccountingPeriod
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Find or create period for this date
        $period = AccountingPeriod::findOrCreateForDate($carbon);

        // Check if period is closed
        if ($period->isClosed()) {
            throw new PeriodClosedException(
                "El período {$period->display_name} está cerrado. No se pueden crear asientos en períodos cerrados."
            );
        }

        // Check lock_date
        if (!$period->canPostDate($carbon)) {
            $lockDate = $period->lock_date->format('Y-m-d');
            throw new PeriodClosedException(
                "La fecha {$carbon->toDateString()} está antes de la fecha de bloqueo ({$lockDate}). No se pueden crear asientos antes de esta fecha."
            );
        }

        return $period;
    }

    /**
     * Close an accounting period.
     * 
     * @param AccountingPeriod $period
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return AccountingPeriod
     */
    public function close(AccountingPeriod $period, ?int $userId = null): AccountingPeriod
    {
        if ($period->isClosed()) {
            throw new \RuntimeException("El período ya está cerrado.");
        }

        $period->update([
            'status' => AccountingPeriod::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $userId ?? Auth::id(),
        ]);

        return $period->fresh();
    }

    /**
     * Close period with validation - prevents close if drafts exist.
     * 
     * @param AccountingPeriod $period
     * @param bool $force Force close even with warnings (not errors)
     * @throws \RuntimeException If validation fails
     */
    public function closeWithValidation(AccountingPeriod $period, bool $force = false): AccountingPeriod
    {
        $validation = $this->validatePeriodForClose($period);

        if (!$validation['can_close']) {
            throw new \RuntimeException($validation['errors'][0] ?? 'No se puede cerrar el período.');
        }

        if (!$force && !empty($validation['warnings'])) {
            throw new \RuntimeException(
                'Hay advertencias pendientes: ' . implode(', ', $validation['warnings'])
            );
        }

        $closedPeriod = $this->close($period);

        Log::info('Período contable cerrado', [
            'period_id' => $period->id,
            'period' => $period->display_name,
            'closed_by' => Auth::id(),
            'summary' => $validation['summary'],
        ]);

        return $closedPeriod;
    }

    /**
     * Validate if a period can be closed.
     * 
     * Checks:
     * - No draft journal entries in period
     * - All entries are balanced
     * 
     * @return array{can_close: bool, errors: array, warnings: array, summary: array}
     */
    public function validatePeriodForClose(AccountingPeriod $period): array
    {
        $errors = [];
        $warnings = [];

        $startDate = $period->start_date;
        $endDate = $period->end_date;

        // Check for draft journal entries
        $draftCount = JournalEntry::where('status', JournalEntryStatus::Draft)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        if ($draftCount > 0) {
            $errors[] = "Hay {$draftCount} asiento(s) en borrador. Debe contabilizarlos o eliminarlos antes de cerrar.";
        }

        // Get summary statistics
        $totalEntries = JournalEntry::where('status', JournalEntryStatus::Posted)
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        $totalDebits = JournalEntryLine::whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
            $q->where('status', JournalEntryStatus::Posted)
                ->whereBetween('date', [$startDate, $endDate]);
        })->sum('base_debit');

        $totalCredits = JournalEntryLine::whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
            $q->where('status', JournalEntryStatus::Posted)
                ->whereBetween('date', [$startDate, $endDate]);
        })->sum('base_credit');

        // Warn if no entries
        if ($totalEntries === 0) {
            $warnings[] = "No hay asientos contabilizados en este período.";
        }

        // Check global balance (should match due to balanced entries)
        $balanceDiff = abs($totalDebits - $totalCredits);
        if ($balanceDiff > 0.01) {
            $warnings[] = "Hay una diferencia de balance de " . number_format($balanceDiff, 2) . ". Verifique los asientos.";
        }

        return [
            'can_close' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'summary' => [
                'total_entries' => $totalEntries,
                'draft_entries' => $draftCount,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'is_balanced' => $balanceDiff < 0.01,
            ],
        ];
    }

    /**
     * Get period close summary for preview.
     */
    public function getPeriodCloseSummary(AccountingPeriod $period): array
    {
        $validation = $this->validatePeriodForClose($period);

        return [
            'period' => [
                'id' => $period->id,
                'name' => $period->display_name,
                'start_date' => $period->start_date->toDateString(),
                'end_date' => $period->end_date->toDateString(),
                'status' => $period->status,
            ],
            'validation' => $validation,
        ];
    }

    /**
     * Reopen a closed accounting period.
     * 
     * Only administrators should be allowed to do this.
     * 
     * @param AccountingPeriod $period
     * @param int|null $userId Optional user ID (defaults to current user)
     * @return AccountingPeriod
     */
    public function reopen(AccountingPeriod $period, ?int $userId = null): AccountingPeriod
    {
        if ($period->isOpen()) {
            throw new \RuntimeException("El período ya está abierto.");
        }

        $period->update([
            'status' => AccountingPeriod::STATUS_OPEN,
            'reopened_at' => now(),
            'reopened_by' => $userId ?? Auth::id(),
        ]);

        Log::info('Período contable reabierto', [
            'period_id' => $period->id,
            'period' => $period->display_name,
            'reopened_by' => $userId ?? Auth::id(),
        ]);

        return $period->fresh();
    }

    /**
     * Set lock date for a period (soft lock).
     * 
     * Prevents postings before this date even if period is open.
     * 
     * @param AccountingPeriod $period
     * @param Carbon|string|null $lockDate
     * @return AccountingPeriod
     */
    public function setLockDate(AccountingPeriod $period, Carbon|string|null $lockDate): AccountingPeriod
    {
        $carbon = $lockDate ? ($lockDate instanceof Carbon ? $lockDate : Carbon::parse($lockDate)) : null;

        $period->update([
            'lock_date' => $carbon,
        ]);

        return $period->fresh();
    }

    /**
     * Get current open period (or create it).
     */
    public function getCurrentPeriod(): AccountingPeriod
    {
        return AccountingPeriod::findOrCreateForDate(now());
    }

    /**
     * Initialize periods for a year (create all 12 months).
     * 
     * @param int $year
     * @return int Number of periods created
     */
    public function initializeYear(int $year): int
    {
        $created = 0;

        for ($month = 1; $month <= 12; $month++) {
            $exists = AccountingPeriod::where('year', $year)
                ->where('month', $month)
                ->exists();

            if (!$exists) {
                AccountingPeriod::create([
                    'year' => $year,
                    'month' => $month,
                    'status' => AccountingPeriod::STATUS_OPEN,
                ]);
                $created++;
            }
        }

        return $created;
    }
}
