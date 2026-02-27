<?php

namespace App\Services;

use App\Enums\JournalEntryStatus;
use App\Exceptions\Accounting\JournalEntryNotBalancedException;
use App\Exceptions\Accounting\PeriodClosedException;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JournalPostingService
 * 
 * Handles posting and reversing journal entries with all validations.
 */
class JournalPostingService
{
    public function __construct(
        protected AccountingPeriodService $periodService,
        protected DailyBalanceService $dailyBalanceService
    ) {}

    /**
     * Post a draft journal entry.
     * 
     * @param JournalEntry $entry
     * @throws JournalEntryNotBalancedException
     * @throws PeriodClosedException
     * @throws \RuntimeException
     * @return JournalEntry
     */
    public function post(JournalEntry $entry): JournalEntry
    {
        // 1. Validate current status
        if ($entry->status !== JournalEntryStatus::Draft) {
            throw new \RuntimeException(
                "Solo se pueden contabilizar asientos en estado Borrador. Estado actual: {$entry->status->label()}"
            );
        }

        // 2. Validate minimum lines
        if ($entry->lines->count() < 2) {
            throw new \RuntimeException(
                "El asiento debe tener al menos 2 líneas."
            );
        }

        // 3. Validate balance
        if (!$entry->is_balanced) {
            throw new JournalEntryNotBalancedException(
                $entry->total_base_debit,
                $entry->total_base_credit
            );
        }

        // 4. Validate period is open
        $this->periodService->assertOpen($entry->date);

        // 5. Post in transaction
        return DB::transaction(function () use ($entry) {
            $entry->update([
                'status' => JournalEntryStatus::Posted,
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            // Update daily balances for affected accounts
            $this->dailyBalanceService->updateBalancesForEntry($entry);

            return $entry->fresh(['lines', 'lines.account']);
        });
    }

    /**
     * Reverse a posted journal entry.
     * 
     * Creates a new entry with inverted debits/credits and marks original as reversed.
     * 
     * @param JournalEntry $entry
     * @param string|null $description Optional description for reversal entry
     * @throws PeriodClosedException
     * @throws \RuntimeException
     * @return JournalEntry The new reversal entry
     */
    public function reverse(JournalEntry $entry, ?string $description = null): JournalEntry
    {
        // 1. Validate current status
        if ($entry->status !== JournalEntryStatus::Posted) {
            throw new \RuntimeException(
                "Solo se pueden reversar asientos contabilizados. Estado actual: {$entry->status->label()}"
            );
        }

        // 2. Validate period is open for reversal date (today)
        $reversalDate = now()->toDateString();
        $this->periodService->assertOpen($reversalDate);

        // 3. Create reversal in transaction
        return DB::transaction(function () use ($entry, $description, $reversalDate) {
            // Create reversal entry
            $reversalEntry = JournalEntry::create([
                'date' => $reversalDate,
                'description' => $description ?? "Reversión de {$entry->entry_number}: {$entry->description}",
                'status' => JournalEntryStatus::Posted,
                'reversal_of_entry_id' => $entry->id,
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => now(),
            ]);

            // Create inverted lines
            foreach ($entry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversalEntry->id,
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'currency_code' => $line->currency_code,
                    'exchange_rate' => $line->exchange_rate,
                    // Invert debit/credit
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ]);
            }

            // Mark original as reversed
            $entry->update([
                'status' => JournalEntryStatus::Reversed,
                'reversed_by' => Auth::id(),
                'reversed_at' => now(),
            ]);

            // Update daily balances - add reversal entry, subtract original
            $this->dailyBalanceService->updateBalancesForEntry($reversalEntry);

            return $reversalEntry->fresh(['lines', 'lines.account']);
        });
    }

    /**
     * Create a journal entry from an array of data.
     * 
     * @param array $data Header data
     * @param array $lines Lines data
     * @return JournalEntry
     */
    public function create(array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines) {
            $entry = JournalEntry::create([
                'date' => $data['date'],
                'description' => $data['description'],
                'status' => JournalEntryStatus::Draft,
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
            ]);

            foreach ($lines as $lineData) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lineData['account_id'],
                    'description' => $lineData['description'] ?? null,
                    'currency_code' => $lineData['currency_code'] ?? 'DOP',
                    'exchange_rate' => $lineData['exchange_rate'] ?? 1,
                    'debit' => $lineData['debit'] ?? 0,
                    'credit' => $lineData['credit'] ?? 0,
                ]);
            }

            return $entry->fresh(['lines', 'lines.account']);
        });
    }

    /**
     * Update a draft journal entry.
     * 
     * @param JournalEntry $entry
     * @param array $data Header data
     * @param array $lines Lines data
     * @return JournalEntry
     * @throws \RuntimeException
     */
    public function update(JournalEntry $entry, array $data, array $lines): JournalEntry
    {
        if ($entry->status !== JournalEntryStatus::Draft) {
            throw new \RuntimeException(
                "Solo se pueden editar asientos en estado Borrador."
            );
        }

        return DB::transaction(function () use ($entry, $data, $lines) {
            // Update header
            $entry->update([
                'date' => $data['date'],
                'description' => $data['description'],
            ]);

            // Delete existing lines
            $entry->lines()->delete();

            // Create new lines
            foreach ($lines as $lineData) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lineData['account_id'],
                    'description' => $lineData['description'] ?? null,
                    'currency_code' => $lineData['currency_code'] ?? 'DOP',
                    'exchange_rate' => $lineData['exchange_rate'] ?? 1,
                    'debit' => $lineData['debit'] ?? 0,
                    'credit' => $lineData['credit'] ?? 0,
                ]);
            }

            return $entry->fresh(['lines', 'lines.account']);
        });
    }
}
