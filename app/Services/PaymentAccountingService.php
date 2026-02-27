<?php

namespace App\Services;

use App\Enums\JournalEntryStatus;
use App\Enums\PaymentType;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for creating journal entries from payments.
 * 
 * Handles the accounting integration when payments are posted or voided.
 * Supports exchange rate differences (FX gain/loss).
 */
class PaymentAccountingService
{
    public function __construct(
        protected AccountingSettingService $accountingSettingService,
        protected JournalPostingService $journalPostingService,
    ) {}

    /**
     * Create and post a journal entry for a payment.
     * 
     * For inbound payments (customer receipts):
     * - Debit: Cash/Bank (amount received)
     * - Credit: Accounts Receivable (amount applied to invoices)
     * - Debit/Credit: FX Gain/Loss (if exchange rate differs)
     * 
     * @throws InvalidArgumentException If accounting settings are not configured
     * @throws \Exception If entry creation fails
     */
    public function createJournalEntryForPayment(Payment $payment): JournalEntry
    {
        // Check idempotency - don't create duplicate entries
        $existing = JournalEntry::where('source_type', 'payment')
            ->where('source_id', $payment->id)
            ->whereNotIn('status', [JournalEntryStatus::Reversed])
            ->first();

        if ($existing) {
            Log::info('Journal entry already exists for payment', [
                'payment_id' => $payment->id,
                'journal_entry_id' => $existing->id,
            ]);
            return $existing;
        }

        // Validate accounting configuration
        $this->validateAccountingSettings($payment);

        // Get required accounts based on payment type
        if ($payment->type === PaymentType::Inbound) {
            return $this->createInboundPaymentEntry($payment);
        }

        // Future: outbound payments to suppliers
        throw new InvalidArgumentException('Outbound payments not yet supported.');
    }

    /**
     * Create journal entry for inbound payment (customer receipt).
     */
    protected function createInboundPaymentEntry(Payment $payment): JournalEntry
    {
        $settings = $this->accountingSettingService->get();

        // Get accounts
        $cashAccount = $payment->bankAccount ?? $this->accountingSettingService->getAccount('cash');
        $arAccount = $this->accountingSettingService->getAccount('ar');
        $fxGainAccount = $this->accountingSettingService->getAccount('exchangeGain');
        $fxLossAccount = $this->accountingSettingService->getAccount('exchangeLoss');

        if (!$cashAccount) {
            throw new InvalidArgumentException(
                'La cuenta de Banco/Caja no está configurada. Seleccione una cuenta bancaria o configure en Configuración Contable.'
            );
        }

        if (!$arAccount) {
            throw new InvalidArgumentException(
                'La cuenta de Cuentas por Cobrar no está configurada en Configuración Contable.'
            );
        }

        $payment->load('allocations.invoice');

        return DB::transaction(function () use ($payment, $cashAccount, $arAccount, $fxGainAccount, $fxLossAccount) {
            // Get withholding accounts if needed
            $isrRetentionAccount = $this->accountingSettingService->getAccount('isrRetention');
            $itbisRetentionAccount = $this->accountingSettingService->getAccount('itbisRetention');

            // Create journal entry header
            $journalEntry = JournalEntry::create([
                'date' => $payment->payment_date,
                'description' => $this->buildDescription($payment),
                'status' => JournalEntryStatus::Draft,
                'source_type' => 'payment',
                'source_id' => $payment->id,
            ]);

            $totalFxDifference = 0;

            // Calculate net amount (amount received minus withholdings)
            $isrWithholding = (float) ($payment->isr_withholding_amount ?? 0);
            $itbisWithholding = (float) ($payment->itbis_withholding_amount ?? 0);
            $netAmount = $payment->amount - $isrWithholding - $itbisWithholding;
            $netBaseAmount = $netAmount * $payment->exchange_rate;

            // Line 1: Debit Cash/Bank (NET amount received after withholdings)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $cashAccount->id,
                'description' => "Cobro {$payment->payment_number}",
                'currency_code' => $payment->currency_code,
                'exchange_rate' => $payment->exchange_rate,
                'debit' => $netAmount,
                'credit' => 0,
                'base_debit' => $netBaseAmount,
                'base_credit' => 0,
            ]);

            // Line 2: Debit ISR Withholding (if applicable)
            if ($isrWithholding > 0.01 && $isrRetentionAccount) {
                $isrBaseAmount = $isrWithholding * $payment->exchange_rate;
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $isrRetentionAccount->id,
                    'description' => "Retención ISR - {$payment->payment_number}",
                    'currency_code' => $payment->currency_code,
                    'exchange_rate' => $payment->exchange_rate,
                    'debit' => $isrWithholding,
                    'credit' => 0,
                    'base_debit' => $isrBaseAmount,
                    'base_credit' => 0,
                ]);
            }

            // Line 3: Debit ITBIS Withholding (if applicable)
            if ($itbisWithholding > 0.01 && $itbisRetentionAccount) {
                $itbisBaseAmount = $itbisWithholding * $payment->exchange_rate;
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $itbisRetentionAccount->id,
                    'description' => "Retención ITBIS - {$payment->payment_number}",
                    'currency_code' => $payment->currency_code,
                    'exchange_rate' => $payment->exchange_rate,
                    'debit' => $itbisWithholding,
                    'credit' => 0,
                    'base_debit' => $itbisBaseAmount,
                    'base_credit' => 0,
                ]);
            }

            // Lines for each allocation - Credit AR
            foreach ($payment->allocations as $allocation) {
                $invoice = $allocation->invoice;

                // Calculate the base amount at invoice rate vs payment rate
                $amountAtInvoiceRate = $allocation->amount_applied * ($invoice->exchange_rate ?? 1);
                $amountAtPaymentRate = $allocation->amount_applied * $payment->exchange_rate;
                $fxDifference = $amountAtPaymentRate - $amountAtInvoiceRate;
                $totalFxDifference += $fxDifference;

                // Credit AR at invoice exchange rate (matching the original invoice entry)
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $arAccount->id,
                    'description' => "Factura {$invoice->ncf}",
                    'currency_code' => $payment->currency_code,
                    'exchange_rate' => $invoice->exchange_rate ?? 1,
                    'debit' => 0,
                    'credit' => $allocation->amount_applied,
                    'base_debit' => 0,
                    'base_credit' => $amountAtInvoiceRate,
                ]);
            }

            // Handle Unapplied Amount (Saldo a favor / Anticipo)
            $totalAllocated = $payment->allocations->sum('amount_applied');
            $unappliedAmount = $payment->amount - $totalAllocated;

            if ($unappliedAmount > 0.01) {
                // Credit AR for the unapplied amount at the PAYMENT exchange rate (no FX diff)
                $unappliedBaseAmount = $unappliedAmount * $payment->exchange_rate;

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $arAccount->id,
                    'description' => "Anticipo / Saldo a favor",
                    'currency_code' => $payment->currency_code,
                    'exchange_rate' => $payment->exchange_rate,
                    'debit' => 0,
                    'credit' => $unappliedAmount,
                    'base_debit' => 0,
                    'base_credit' => $unappliedBaseAmount,
                ]);
            }

            // FX Gain/Loss line if there's a difference
            if (abs($totalFxDifference) > 0.01) {
                if ($totalFxDifference > 0) {
                    // FX Gain (credit) - payment rate higher than invoice rate
                    if (!$fxGainAccount) {
                        throw new InvalidArgumentException(
                            'Se detectó una Ganancia Cambiaria pero la cuenta de "Ganancia por Diferencial Cambiario" no está configurada.'
                        );
                    }

                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $fxGainAccount->id,
                        'description' => "Ganancia cambiaria",
                        'currency_code' => 'DOP', // Base currency
                        'exchange_rate' => 1,
                        'debit' => 0,
                        'credit' => abs($totalFxDifference),
                        'base_debit' => 0,
                        'base_credit' => abs($totalFxDifference),
                    ]);
                } else {
                    // FX Loss (debit) - payment rate lower than invoice rate
                    if (!$fxLossAccount) {
                        throw new InvalidArgumentException(
                            'Se detectó una Pérdida Cambiaria pero la cuenta de "Pérdida por Diferencial Cambiario" no está configurada.'
                        );
                    }

                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $fxLossAccount->id,
                        'description' => "Pérdida cambiaria",
                        'currency_code' => 'DOP', // Base currency
                        'exchange_rate' => 1,
                        'debit' => abs($totalFxDifference),
                        'credit' => 0,
                        'base_debit' => abs($totalFxDifference),
                        'base_credit' => 0,
                    ]);
                }
            }

            // Post the entry automatically
            $this->journalPostingService->post($journalEntry);

            Log::info('Journal entry created and posted for payment', [
                'payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'journal_entry_id' => $journalEntry->id,
                'journal_entry_number' => $journalEntry->entry_number,
                'amount' => $payment->amount,
                'fx_difference' => $totalFxDifference,
            ]);

            return $journalEntry->fresh(['lines', 'lines.account']);
        });
    }

    /**
     * Reverse the journal entry for a voided payment.
     */
    public function reverseJournalEntryForPayment(Payment $payment): ?JournalEntry
    {
        // Find the original journal entry
        $originalEntry = JournalEntry::where('source_type', 'payment')
            ->where('source_id', $payment->id)
            ->where('status', JournalEntryStatus::Posted)
            ->first();

        if (!$originalEntry) {
            Log::warning('No posted journal entry found to reverse for payment', [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        $reversalEntry = $this->journalPostingService->reverse(
            $originalEntry,
            "Anulación pago: {$payment->payment_number}"
        );

        Log::info('Journal entry reversed for voided payment', [
            'payment_id' => $payment->id,
            'original_entry_id' => $originalEntry->id,
            'reversal_entry_id' => $reversalEntry->id,
        ]);

        return $reversalEntry;
    }

    /**
     * Check if accounting is configured for payment integration.
     */
    public function isConfigured(): bool
    {
        try {
            $settings = $this->accountingSettingService->get();
            return $settings->ar_account_id !== null
                && ($settings->cash_account_id !== null || $settings->bank_account_id !== null);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Validate that required accounting settings exist.
     */
    protected function validateAccountingSettings(Payment $payment): void
    {
        $settings = $this->accountingSettingService->get();

        if (!$settings->ar_account_id) {
            throw new InvalidArgumentException(
                'La cuenta de Cuentas por Cobrar no está configurada en Configuración Contable.'
            );
        }

        // Must have either bank account on payment or default cash/bank account
        if (!$payment->bank_account_id && !$settings->cash_account_id && !$settings->bank_account_id) {
            throw new InvalidArgumentException(
                'Debe seleccionar una cuenta bancaria o configurar la cuenta de Caja/Banco en Configuración Contable.'
            );
        }
    }

    /**
     * Build a descriptive text for the journal entry.
     */
    protected function buildDescription(Payment $payment): string
    {
        $description = "Cobro {$payment->payment_number}";

        if ($payment->customer) {
            $description .= " - {$payment->customer->name}";
        }

        if ($payment->reference) {
            $description .= " (Ref: {$payment->reference})";
        }

        return $description;
    }
}
