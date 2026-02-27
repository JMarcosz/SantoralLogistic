<?php

namespace App\Services;

use App\Exceptions\FiscalSequenceExhaustedException;
use App\Exceptions\NoFiscalSequenceAvailableException;
use App\Models\FiscalSequence;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing fiscal sequence numbering (NCF - Números de Comprobante Fiscal).
 * 
 * Handles the allocation of NCF numbers from authorized ranges with:
 * - Concurrency control via database transactions and row locking
 * - Automatic sequential numbering
 * - Validation of range exhaustion and validity periods
 */
class FiscalNumberService
{
    /**
     * Get the next available NCF for a given type and optional series.
     * 
     * This method is thread-safe and prevents duplicate NCF assignment
     * through the use of database transactions and SELECT FOR UPDATE.
     * 
     * @param string $ncfType NCF type (e.g., 'B01', 'B02', 'B14')
     * @param string|null $series Optional series identifier
     * @return string The next NCF to use
     * 
     * @throws NoFiscalSequenceAvailableException If no valid sequence exists
     * @throws FiscalSequenceExhaustedException If the sequence is exhausted
     */
    public function getNextNcf(string $ncfType, ?string $series = null): string
    {
        return DB::transaction(function () use ($ncfType, $series) {
            // Find and lock an active sequence for this type/series
            $sequence = $this->findActiveSequence($ncfType, $series);

            // Calculate what the next NCF should be
            $nextNcf = $this->calculateNextNcf($sequence);

            // Validate that we haven't exceeded the range
            $this->validateNcfNotExceeded($sequence, $nextNcf);

            // Update the current_ncf in the database
            $sequence->current_ncf = $nextNcf;
            $sequence->save();

            return $nextNcf;
        });
    }

    /**
     * Find an active and valid fiscal sequence for the given type/series.
     * 
     * Uses SELECT FOR UPDATE to lock the row and prevent concurrent modifications.
     * 
     * @param string $ncfType
     * @param string|null $series
     * @return FiscalSequence
     * 
     * @throws NoFiscalSequenceAvailableException
     */
    protected function findActiveSequence(string $ncfType, ?string $series): FiscalSequence
    {
        $sequence = FiscalSequence::query()
            ->forType($ncfType, $series)
            ->active()
            ->validAt(now())
            ->lockForUpdate()  // Critical: lock the row to prevent concurrent access
            ->first();

        if (!$sequence) {
            throw new NoFiscalSequenceAvailableException($ncfType, $series);
        }

        // Additional check: ensure sequence is not exhausted
        if ($sequence->isExhausted()) {
            throw FiscalSequenceExhaustedException::forSequence($sequence);
        }

        return $sequence;
    }

    /**
     * Calculate the next NCF based on the sequence's current state.
     * 
     * @param FiscalSequence $sequence
     * @return string
     */
    protected function calculateNextNcf(FiscalSequence $sequence): string
    {
        // If no NCF has been issued yet, start from the beginning
        if ($sequence->current_ncf === null) {
            return $sequence->ncf_from;
        }

        // Otherwise, increment the current NCF
        return $this->incrementNcf($sequence->current_ncf);
    }

    /**
     * Increment an NCF string by extracting the numeric suffix and adding 1.
     * 
     * Format expected: prefix + numeric suffix
     * Example: B01-00000000001 -> B01-00000000002
     * 
     * @param string $ncf
     * @return string
     * 
     * @throws \InvalidArgumentException If NCF format is invalid
     */
    protected function incrementNcf(string $ncf): string
    {
        // Extract the numeric suffix using regex
        if (!preg_match('/^(.*)(\d+)$/', $ncf, $matches)) {
            throw new \InvalidArgumentException("Formato de NCF inválido: {$ncf}");
        }

        $prefix = $matches[1];
        $numeric = $matches[2];
        $width = strlen($numeric);

        // Increment the numeric part
        $nextNumeric = (int) $numeric + 1;

        // Pad with zeros to maintain the same width
        $paddedNumeric = str_pad((string) $nextNumeric, $width, '0', STR_PAD_LEFT);

        return $prefix . $paddedNumeric;
    }

    /**
     * Validate that the next NCF doesn't exceed the range's upper limit.
     * 
     * @param FiscalSequence $sequence
     * @param string $nextNcf
     * 
     * @throws FiscalSequenceExhaustedException
     */
    protected function validateNcfNotExceeded(FiscalSequence $sequence, string $nextNcf): void
    {
        // String comparison works for NCF format since they're padded
        if (strcmp($nextNcf, $sequence->ncf_to) > 0) {
            // Update the sequence to mark it as exhausted before throwing
            $sequence->current_ncf = $sequence->ncf_to;
            $sequence->save();

            throw FiscalSequenceExhaustedException::forSequence($sequence);
        }
    }

    /**
     * Preview the next NCF without actually consuming it.
     * Useful for displaying to users before finalizing a transaction.
     * 
     * @param string $ncfType
     * @param string|null $series
     * @return string|null The next NCF, or null if no sequence available
     */
    public function previewNextNcf(string $ncfType, ?string $series = null): ?string
    {
        $sequence = FiscalSequence::query()
            ->forType($ncfType, $series)
            ->active()
            ->validAt(now())
            ->first();

        if (!$sequence || $sequence->isExhausted()) {
            return null;
        }

        return $sequence->peekNextNcf();
    }

    /**
     * Check if a valid sequence exists for a given type/series.
     * 
     * @param string $ncfType
     * @param string|null $series
     * @return bool
     */
    public function hasAvailableSequence(string $ncfType, ?string $series = null): bool
    {
        $sequence = FiscalSequence::query()
            ->forType($ncfType, $series)
            ->active()
            ->validAt(now())
            ->first();

        return $sequence && !$sequence->isExhausted();
    }
}
