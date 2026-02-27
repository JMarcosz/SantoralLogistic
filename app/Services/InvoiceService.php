<?php

namespace App\Services;

use App\Exceptions\FiscalSequenceExhaustedException;
use App\Exceptions\NoFiscalSequenceAvailableException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PreInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Service for creating and managing fiscal invoices with NCF assignment.
 */
class InvoiceService
{
    public function __construct(
        protected FiscalNumberService $fiscalNumberService,
        protected InvoiceAccountingService $invoiceAccountingService,
    ) {}

    /**
     * Create a fiscal invoice from a PreInvoice.
     * 
     * @param PreInvoice $preInvoice
     * @param string|null $ncfType NCF type (B01, B02, B14, etc.). If null, uses customer's default.
     * @param string|null $series Optional series for NCF. If null, uses customer's series or none.
     * @return Invoice
     * 
     * @throws InvalidArgumentException
     * @throws \App\Exceptions\NoFiscalSequenceAvailableException
     * @throws \App\Exceptions\FiscalSequenceExhaustedException
     */
    public function createFromPreInvoice(
        PreInvoice $preInvoice,
        ?string $ncfType = null,
        ?string $series = null
    ): Invoice {
        // Validations
        $this->validatePreInvoiceFacturable($preInvoice);

        // Determine NCF type and series
        $ncfType = $ncfType ?? $preInvoice->customer->ncf_type_default;
        $series = $series ?? $preInvoice->customer->series;

        if (empty($ncfType)) {
            throw new InvalidArgumentException('NCF type must be specified or customer must have a default NCF type.');
        }

        // Validate customer fiscal data
        $this->validateCustomerFiscalData($preInvoice->customer, $ncfType);

        // Calculate amounts for DGII
        $amounts = $this->calculateAmounts($preInvoice);
        $this->validateAmounts($amounts);

        // Create invoice within transaction
        return DB::transaction(function () use ($preInvoice, $ncfType, $series, $amounts) {
            try {
                // Assign NCF from fiscal number service
                $ncf = $this->fiscalNumberService->getNextNcf($ncfType, $series);
            } catch (NoFiscalSequenceAvailableException $e) {
                // Log warning with context
                Log::channel('fiscal')->warning('No fiscal sequence available for invoice generation', [
                    'ncf_type' => $ncfType,
                    'series' => $series,
                    'pre_invoice_id' => $preInvoice->id,
                    'pre_invoice_number' => $preInvoice->number,
                    'customer_id' => $preInvoice->customer_id,
                    'customer_name' => $preInvoice->customer->name,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            } catch (FiscalSequenceExhaustedException $e) {
                // Log warning with detailed context
                Log::channel('fiscal')->warning('Fiscal sequence exhausted', [
                    'sequence_id' => $e->sequence->id ?? null,
                    'ncf_type' => $e->sequence->ncf_type ?? $ncfType,
                    'series' => $e->sequence->series ?? $series,
                    'current_ncf' => $e->sequence->current_ncf ?? null,
                    'ncf_to' => $e->sequence->ncf_to ?? null,
                    'pre_invoice_id' => $preInvoice->id,
                    'pre_invoice_number' => $preInvoice->number,
                    'customer_id' => $preInvoice->customer_id,
                    'user_id' => auth()->id(),
                ]);
                throw $e;
            }

            // Create invoice header
            $invoice = Invoice::create([
                'pre_invoice_id' => $preInvoice->id,
                'ncf' => $ncf,
                'ncf_type' => $ncfType,
                'customer_id' => $preInvoice->customer_id,
                'shipping_order_id' => $preInvoice->shipping_order_id,
                'currency_code' => $preInvoice->currency_code,
                'issue_date' => now(),
                'due_date' => $preInvoice->due_date,
                'status' => Invoice::STATUS_ISSUED,
                'subtotal_amount' => $amounts['subtotal'],
                'tax_amount' => $amounts['tax'],
                'total_amount' => $amounts['total'],
                'taxable_amount' => $amounts['taxable'],
                'exempt_amount' => $amounts['exempt'],
                'notes' => "Generada desde Pre-Factura {$preInvoice->number}",
            ]);

            // Create invoice lines
            foreach ($preInvoice->lines as $preLine) {
                $invoice->lines()->create([
                    'pre_invoice_line_id' => $preLine->id,
                    'code' => $preLine->code,
                    'description' => $preLine->description,
                    'qty' => $preLine->qty,
                    'unit_price' => $preLine->unit_price,
                    'amount' => $preLine->amount,
                    'tax_amount' => $preLine->tax_amount,
                    'currency_code' => $preLine->currency_code,
                    'sort_order' => $preLine->sort_order,
                ]);
            }

            // Mark PreInvoice as invoiced
            $preInvoice->update(['invoiced_at' => now()]);

            // Log successful invoice creation
            Log::channel('fiscal')->info('Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->number,
                'ncf' => $invoice->ncf,
                'ncf_type' => $invoice->ncf_type,
                'customer_id' => $invoice->customer_id,
                'customer_name' => $invoice->customer->name,
                'pre_invoice_id' => $preInvoice->id,
                'pre_invoice_number' => $preInvoice->number,
                'total_amount' => $invoice->total_amount,
                'currency_code' => $invoice->currency_code,
                'user_id' => auth()->id(),
            ]);

            // Create journal entry if accounting integration is available
            $this->createAccountingEntry($invoice);

            return $invoice;
        });
    }

    /**
     * Validate that the PreInvoice can be converted to a fiscal invoice.
     */
    protected function validatePreInvoiceFacturable(PreInvoice $preInvoice): void
    {
        if ($preInvoice->status !== PreInvoice::STATUS_ISSUED) {
            throw new InvalidArgumentException(
                "PreInvoice must be in 'issued' status to generate a fiscal invoice. Current status: {$preInvoice->status}"
            );
        }

        if ($preInvoice->hasBeenInvoiced()) {
            throw new InvalidArgumentException(
                "PreInvoice {$preInvoice->number} has already been converted to a fiscal invoice."
            );
        }
    }

    /**
     * Validate customer has required fiscal data.
     */
    protected function validateCustomerFiscalData(Customer $customer, string $ncfType): void
    {
        if (empty($customer->tax_id)) {
            throw new InvalidArgumentException(
                "Customer '{$customer->name}' must have a tax_id (RNC/Cédula) to issue fiscal invoices."
            );
        }

        if (empty($customer->tax_id_type)) {
            throw new InvalidArgumentException(
                "Customer '{$customer->name}' must have a tax_id_type defined."
            );
        }

        // Future: add validation for coherence between ncf_type and tax_id_type
        // Example: B01 requires RNC, B02 can be RNC or Cédula, etc.
    }

    /**
     * Calculate amounts for DGII reporting.
     * 
     * @return array{subtotal: float, tax: float, total: float, taxable: float, exempt: float}
     */
    protected function calculateAmounts(PreInvoice $preInvoice): array
    {
        $subtotal = (float) $preInvoice->subtotal_amount;
        $tax = (float) $preInvoice->tax_amount;
        $total = (float) $preInvoice->total_amount;

        // For DGII 607/608
        // taxable_amount = base imponible (amount subject to tax)
        // exempt_amount = monto exento (MVP: 0, future: calculate based on product/service)
        $taxable = $subtotal;  // Base imponible
        $exempt = 0.0;  // MVP: no exemptions

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'taxable' => $taxable,
            'exempt' => $exempt,
        ];
    }

    /**
     * Validate that amounts are coherent.
     */
    protected function validateAmounts(array $amounts): void
    {
        // Check for negative amounts
        if ($amounts['subtotal'] < 0 || $amounts['tax'] < 0 || $amounts['total'] < 0) {
            throw new InvalidArgumentException('Invoice amounts cannot be negative.');
        }

        // Validate total = subtotal + tax (with small tolerance for floating point)
        $expectedTotal = $amounts['subtotal'] + $amounts['tax'];
        if (abs($expectedTotal - $amounts['total']) > 0.01) {
            throw new InvalidArgumentException(
                "Invoice total amount ({$amounts['total']}) does not match subtotal + tax ({$expectedTotal})."
            );
        }

        // Validate taxable + exempt = subtotal
        $expectedSubtotal = $amounts['taxable'] + $amounts['exempt'];
        if (abs($expectedSubtotal - $amounts['subtotal']) > 0.01) {
            throw new InvalidArgumentException(
                "Taxable amount + Exempt amount must equal subtotal."
            );
        }
    }

    /**
     * Cancel an invoice.
     * 
     * @param Invoice $invoice
     * @param string $reason
     * @return Invoice
     */
    public function cancelInvoice(Invoice $invoice, string $reason): Invoice
    {
        $invoice->cancel($reason);

        // Reverse journal entry if accounting integration is available
        $this->reverseAccountingEntry($invoice);

        return $invoice->fresh();
    }

    /**
     * Create accounting journal entry for invoice.
     * Silently skips if accounting is not configured.
     */
    protected function createAccountingEntry(Invoice $invoice): void
    {
        try {
            if ($this->invoiceAccountingService->isConfigured()) {
                $this->invoiceAccountingService->createJournalEntryForInvoice($invoice);
            }
        } catch (\Exception $e) {
            // Log but don't fail invoice creation
            Log::warning('Failed to create accounting entry for invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reverse accounting journal entry for cancelled invoice.
     * Silently skips if no journal entry exists.
     */
    protected function reverseAccountingEntry(Invoice $invoice): void
    {
        try {
            $this->invoiceAccountingService->reverseJournalEntryForInvoice($invoice);
        } catch (\Exception $e) {
            // Log but don't fail invoice cancellation
            Log::warning('Failed to reverse accounting entry for invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
