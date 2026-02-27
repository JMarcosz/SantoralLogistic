<?php

namespace App\Listeners;

use App\Events\PaymentVoided;
use App\Services\AuditService;
use App\Services\PaymentAccountingService;
use Illuminate\Support\Facades\Log;

/**
 * Reverses the journal entry when a payment is voided.
 * 
 * Note: This listener runs synchronously to maintain transaction atomicity.
 */
class ReversePaymentJournalEntry
{
    public function __construct(
        protected PaymentAccountingService $accountingService,
        protected AuditService $auditService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentVoided $event): void
    {
        $payment = $event->payment;

        try {
            if ($this->accountingService->isConfigured()) {
                $reversalEntry = $this->accountingService->reverseJournalEntryForPayment($payment);

                if ($reversalEntry) {
                    Log::info('Journal entry reversed for voided payment via event', [
                        'payment_id' => $payment->id,
                        'payment_number' => $payment->payment_number,
                        'reversal_entry_id' => $reversalEntry->id,
                    ]);
                }
            }

            // Log audit trail
            $this->auditService->logPaymentVoided($payment);
        } catch (\Exception $e) {
            Log::error('Failed to reverse journal entry for payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
