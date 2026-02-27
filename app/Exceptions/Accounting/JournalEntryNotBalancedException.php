<?php

namespace App\Exceptions\Accounting;

use Exception;

/**
 * Exception thrown when a journal entry cannot be posted.
 */
class JournalEntryNotBalancedException extends Exception
{
    public function __construct(
        public readonly float $totalDebit,
        public readonly float $totalCredit,
        ?string $message = null
    ) {
        $diff = abs($totalDebit - $totalCredit);
        $message = $message ?? "El asiento no está balanceado. Débito: {$totalDebit}, Crédito: {$totalCredit}, Diferencia: {$diff}";

        parent::__construct($message);
    }
}
