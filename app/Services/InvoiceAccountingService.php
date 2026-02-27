<?php

namespace App\Services;

use App\Enums\JournalEntryStatus;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\TaxMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for creating journal entries from invoices.
 * 
 * Handles the accounting integration when invoices are issued or cancelled.
 */
class InvoiceAccountingService
{
    public function __construct(
        protected AccountingSettingService $accountingSettingService,
        protected JournalPostingService $journalPostingService,
    ) {}

    /**
     * Create and post a journal entry for an invoice.
     * 
     * Entry structure:
     * - Debit AR (total_amount)
     * - Credit Revenue (subtotal_amount)
     * - Credit Tax Payable (tax_amount)
     * 
     * @throws InvalidArgumentException If accounting settings are not configured
     * @throws \Exception If entry creation fails
     */
    public function createJournalEntryForInvoice(Invoice $invoice): JournalEntry
    {
        // Check idempotency - don't create duplicate entries
        $existing = JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->whereNotIn('status', [JournalEntryStatus::Reversed])
            ->first();

        if ($existing) {
            Log::info('Journal entry already exists for invoice', [
                'invoice_id' => $invoice->id,
                'journal_entry_id' => $existing->id,
            ]);
            return $existing;
        }

        // Validate accounting configuration
        $this->validateAccountingSettings();

        // Get required accounts
        $arAccount = $this->accountingSettingService->getAccount('ar');
        $revenueAccount = $this->accountingSettingService->getAccount('revenue');
        $taxAccount = $this->getTaxPayableAccount();

        if (!$arAccount || !$revenueAccount) {
            throw new InvalidArgumentException(
                'Accounting settings not configured. Please set up AR and Revenue accounts.'
            );
        }

        return DB::transaction(function () use ($invoice, $arAccount, $revenueAccount, $taxAccount) {
            // Create journal entry header
            $journalEntry = JournalEntry::create([
                'date' => $invoice->issue_date,
                'description' => $this->buildDescription($invoice),
                'status' => JournalEntryStatus::Draft,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
            ]);

            // Line 1: Debit Accounts Receivable (total including tax)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $arAccount->id,
                'description' => "Cliente: {$invoice->customer->name}",
                'currency_code' => $invoice->currency_code,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'base_debit' => $invoice->total_amount,
                'base_credit' => 0,
            ]);

            // Line 2: Credit Revenue (subtotal = taxable + exempt)
            JournalEntryLine::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $revenueAccount->id,
                'description' => "Venta: {$invoice->ncf}",
                'currency_code' => $invoice->currency_code,
                'debit' => 0,
                'credit' => $invoice->subtotal_amount,
                'base_debit' => 0,
                'base_credit' => $invoice->subtotal_amount,
            ]);

            // Line 3: Credit Tax Payable (if tax exists)
            if ($invoice->tax_amount > 0 && $taxAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $taxAccount->id,
                    'description' => "ITBIS 18%: {$invoice->ncf}",
                    'currency_code' => $invoice->currency_code,
                    'debit' => 0,
                    'credit' => $invoice->tax_amount,
                    'base_debit' => 0,
                    'base_credit' => $invoice->tax_amount,
                ]);
            }

            // Post the entry automatically
            $this->journalPostingService->post($journalEntry);

            Log::info('Journal entry created and posted for invoice', [
                'invoice_id' => $invoice->id,
                'invoice_ncf' => $invoice->ncf,
                'journal_entry_id' => $journalEntry->id,
                'journal_entry_number' => $journalEntry->entry_number,
                'total_amount' => $invoice->total_amount,
            ]);

            return $journalEntry->fresh(['lines', 'lines.account']);
        });
    }

    /**
     * Reverse the journal entry for a cancelled invoice.
     */
    public function reverseJournalEntryForInvoice(Invoice $invoice): ?JournalEntry
    {
        // Find the original journal entry
        $originalEntry = JournalEntry::where('source_type', 'invoice')
            ->where('source_id', $invoice->id)
            ->where('status', JournalEntryStatus::Posted)
            ->first();

        if (!$originalEntry) {
            Log::warning('No posted journal entry found to reverse for invoice', [
                'invoice_id' => $invoice->id,
            ]);
            return null;
        }

        $reversalEntry = $this->journalPostingService->reverse(
            $originalEntry,
            "Anulación factura: {$invoice->ncf}"
        );

        Log::info('Journal entry reversed for cancelled invoice', [
            'invoice_id' => $invoice->id,
            'original_entry_id' => $originalEntry->id,
            'reversal_entry_id' => $reversalEntry->id,
        ]);

        return $reversalEntry;
    }

    /**
     * Check if accounting is configured for invoice integration.
     */
    public function isConfigured(): bool
    {
        try {
            $this->validateAccountingSettings();
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Validate that required accounting settings exist.
     */
    protected function validateAccountingSettings(): void
    {
        $settings = $this->accountingSettingService->get();

        if (!$settings->ar_account_id) {
            throw new InvalidArgumentException(
                'La cuenta de Cuentas por Cobrar no está configurada en Configuración Contable.'
            );
        }

        if (!$settings->revenue_account_id) {
            throw new InvalidArgumentException(
                'La cuenta de Ingresos no está configurada en Configuración Contable.'
            );
        }
    }

    /**
     * Get the tax payable account (ITBIS).
     * Prioritizes the default tax mapping, falls back to accounting settings.
     */
    protected function getTaxPayableAccount()
    {
        // Try to get ITBIS account from tax mappings first
        $defaultTax = TaxMapping::active()->default()->first();

        if ($defaultTax && $defaultTax->sales_account_id) {
            return $defaultTax->salesAccount;
        }

        // Fallback to accounting settings
        return $this->accountingSettingService->getAccount('itbisRetention');
    }

    /**
     * Build a descriptive text for the journal entry.
     */
    protected function buildDescription(Invoice $invoice): string
    {
        $description = "Factura fiscal {$invoice->ncf}";

        if ($invoice->customer) {
            $description .= " - {$invoice->customer->name}";
        }

        return $description;
    }
}
