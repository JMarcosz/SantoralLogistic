<?php

namespace App\Listeners;

use App\Events\PaymentPosted;
use App\Services\AuditService;
use App\Services\PaymentAccountingService;
use Illuminate\Support\Facades\Log;

/**
 * Creates a journal entry when a payment is posted.
 * 
 * Accounting logic:
 * - Debit: Bank/Cash account (amount received)
 * - Debit: Withholding accounts (if applicable)
 * - Credit: Accounts Receivable (amount applied to invoices)
 * - Debit/Credit: FX Gain/Loss (if exchange rate differs)
 * 
 * Note: This listener runs synchronously to maintain transaction atomicity.
 */
class CreatePaymentJournalEntry
{
    public function __construct(
        protected PaymentAccountingService $accountingService,
        protected AuditService $auditService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentPosted $event): void
    {
        $payment = $event->payment;

        try {
            if ($this->accountingService->isConfigured()) {
                $journalEntry = $this->accountingService->createJournalEntryForPayment($payment);

                Log::info('Journal entry created for payment via event', [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'journal_entry_id' => $journalEntry->id,
                ]);
            } else {
                Log::warning('Accounting not configured, skipping journal entry creation', [
                    'payment_id' => $payment->id,
                ]);
            }

            // Log audit trail
            $this->auditService->logPaymentPosted($payment);
        } catch (\Exception $e) {
            Log::error('Failed to create journal entry for payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to mark job as failed
        }
    }
}
