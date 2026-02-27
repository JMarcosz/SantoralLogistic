<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PreInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Record a new payment for a pre-invoice (starts in pending status).
     */
    public function recordPayment(PreInvoice $preInvoice, array $data, User $user): Payment
    {
        $this->ensureCanReceivePayment($preInvoice);

        return DB::transaction(function () use ($preInvoice, $data, $user) {
            $payment = $preInvoice->payments()->create([
                'amount' => $data['amount'],
                'currency_code' => $preInvoice->currency_code,
                'payment_date' => $data['payment_date'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => Payment::STATUS_PENDING,
                'created_by' => $user->id,
            ]);

            return $payment;
        });
    }

    /**
     * Approve a pending payment.
     */
    public function approvePayment(Payment $payment, User $approver): Payment
    {
        if (!$payment->isPending()) {
            throw new InvalidArgumentException('Solo se pueden aprobar pagos en estado pendiente.');
        }

        return DB::transaction(function () use ($payment, $approver) {
            $payment->update([
                'status' => Payment::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Recalculate pre-invoice balance
            $payment->preInvoice->recalculateBalance();

            return $payment->fresh();
        });
    }

    /**
     * Void a payment (requires higher permission).
     */
    public function voidPayment(Payment $payment, User $voider, string $reason): Payment
    {
        if ($payment->isVoided()) {
            throw new InvalidArgumentException('Este pago ya fue anulado.');
        }

        return DB::transaction(function () use ($payment, $voider, $reason) {
            $wasApproved = $payment->isApproved();

            $payment->update([
                'status' => Payment::STATUS_VOIDED,
                'voided_by' => $voider->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            // Recalculate balance only if payment was approved
            if ($wasApproved) {
                $payment->preInvoice->recalculateBalance();
            }

            return $payment->fresh();
        });
    }

    /**
     * Quick pay - record and immediately approve (for users with approve permission).
     */
    public function quickPay(PreInvoice $preInvoice, array $data, User $user): Payment
    {
        $payment = $this->recordPayment($preInvoice, $data, $user);
        return $this->approvePayment($payment, $user);
    }

    /**
     * Ensure pre-invoice can receive payments.
     */
    protected function ensureCanReceivePayment(PreInvoice $preInvoice): void
    {
        if ($preInvoice->status === PreInvoice::STATUS_CANCELLED) {
            throw new InvalidArgumentException('No se pueden registrar pagos para pre-facturas canceladas.');
        }

        if ($preInvoice->status === PreInvoice::STATUS_DRAFT) {
            throw new InvalidArgumentException('La pre-factura debe estar emitida para recibir pagos.');
        }

        if ($preInvoice->balance <= 0) {
            throw new InvalidArgumentException('Esta pre-factura ya está completamente pagada.');
        }
    }

    /**
     * Store a new payment with invoice allocations.
     * 
     * Handles:
     * - Atomic transaction (all or nothing)
     * - Allocation validation (amount <= invoice balance)
     * - Multi-currency conversion (DOP payment for USD invoice)
     * - Invoice balance and status updates
     * 
     * @param array $data Validated payment data with allocations
     * @param User $user User creating the payment
     * @return Payment Created payment with allocations
     * @throws InvalidArgumentException If allocation exceeds invoice balance
     */
    public function store(array $data, User $user): Payment
    {
        return DB::transaction(function () use ($data, $user) {
            // Calculate withholdings and net amount
            $isrWithholding = $data['isr_withholding_amount'] ?? 0;
            $itbisWithholding = $data['itbis_withholding_amount'] ?? 0;
            $netAmount = $data['amount'] - $isrWithholding - $itbisWithholding;

            // 1. Create the payment record
            $payment = Payment::create([
                'type' => $data['type'],
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'],
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'currency_code' => $data['currency_code'],
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'base_amount' => $data['amount'] * ($data['exchange_rate'] ?? 1),
                'isr_withholding_amount' => $isrWithholding,
                'itbis_withholding_amount' => $itbisWithholding,
                'net_amount' => $netAmount,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => $user->id,
                'pre_invoice_id' => $this->getPreInvoiceIdFromAllocations($data['allocations'] ?? []),
            ]);

            // 2. Process allocations
            if (!empty($data['allocations'])) {
                $this->processAllocations($payment, $data['allocations']);
            }

            return $payment->fresh(['allocations', 'customer']);
        });
    }

    /**
     * Process payment allocations with validation and multi-currency support.
     * 
     * @param Payment $payment The payment to allocate from
     * @param array $allocations Array of allocations [{invoice_id, amount_applied}]
     * @throws InvalidArgumentException If any allocation exceeds invoice balance
     */
    protected function processAllocations(Payment $payment, array $allocations): void
    {
        foreach ($allocations as $allocationData) {
            $invoice = Invoice::lockForUpdate()->findOrFail($allocationData['invoice_id']);

            // Validate invoice can receive payments
            if ($invoice->isCancelled()) {
                throw new InvalidArgumentException(
                    "La factura {$invoice->number} está cancelada y no puede recibir pagos."
                );
            }

            // Calculate effective amount to apply considering currency conversion
            $amountToApply = $this->calculateAmountToApply(
                $payment,
                $invoice,
                $allocationData['amount_applied']
            );

            // Validate allocation doesn't exceed invoice balance
            if (bccomp((string) $amountToApply, (string) $invoice->balance, 4) > 0) {
                throw new InvalidArgumentException(
                    "El monto a abonar ({$amountToApply}) excede el saldo de la factura {$invoice->number} ({$invoice->balance})."
                );
            }

            // Determine exchange rate for this allocation
            $allocationExchangeRate = $this->calculateAllocationExchangeRate($payment, $invoice);

            // Create allocation - the PaymentAllocation model's boot will trigger
            // invoice->recalculatePayments() automatically
            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount_applied' => $amountToApply,
                'exchange_rate' => $allocationExchangeRate,
                'base_amount_applied' => $amountToApply * $allocationExchangeRate,
            ]);
        }
    }

    /**
     * Calculate the effective amount to apply to an invoice.
     * 
     * Handles multi-currency conversion:
     * - If payment and invoice are same currency: amount_applied directly
     * - If different currencies: convert using payment's exchange_rate
     * 
     * Example: DOP payment of 5,850 at rate 58.50 = 100 USD applied to USD invoice
     * 
     * @param Payment $payment The payment
     * @param Invoice $invoice The target invoice
     * @param float $amountFromForm The amount entered in the form
     * @return float Amount to apply in invoice's currency
     */
    protected function calculateAmountToApply(Payment $payment, Invoice $invoice, float $amountFromForm): float
    {
        // Same currency - no conversion needed
        if ($payment->currency_code === $invoice->currency_code) {
            return $amountFromForm;
        }

        // Multi-currency: Payment is in different currency than invoice
        // The form amount is in payment currency, convert to invoice currency
        // Example: Payment in DOP, Invoice in USD
        // amountFromForm = 5850 DOP, exchange_rate = 58.50 (DOP per USD)
        // Result: 5850 / 58.50 = 100 USD

        $exchangeRate = $payment->exchange_rate ?? 1;
        if ($exchangeRate <= 0) {
            throw new InvalidArgumentException('La tasa de cambio debe ser mayor a cero.');
        }

        // If payment is in local currency (DOP) and invoice is in foreign (USD)
        // Divide by exchange rate to get foreign amount
        if ($payment->currency_code === 'DOP' && $invoice->currency_code !== 'DOP') {
            return $amountFromForm / $exchangeRate;
        }

        // If payment is in foreign currency and invoice is in local (DOP)
        // Multiply by exchange rate to get local amount
        if ($payment->currency_code !== 'DOP' && $invoice->currency_code === 'DOP') {
            return $amountFromForm * $exchangeRate;
        }

        // Other currency pairs - use exchange rate as is
        return $amountFromForm / $exchangeRate;
    }

    /**
     * Calculate the exchange rate for an allocation record.
     */
    protected function calculateAllocationExchangeRate(Payment $payment, Invoice $invoice): float
    {
        if ($payment->currency_code === $invoice->currency_code) {
            return 1.0;
        }

        return $payment->exchange_rate ?? 1.0;
    }

    /**
     * Get pre_invoice_id from the first allocated invoice (for backwards compatibility).
     */
    protected function getPreInvoiceIdFromAllocations(array $allocations): ?int
    {
        if (empty($allocations)) {
            return null;
        }

        $firstInvoiceId = $allocations[0]['invoice_id'] ?? null;
        if (!$firstInvoiceId) {
            return null;
        }

        $invoice = Invoice::find($firstInvoiceId);
        return $invoice?->pre_invoice_id;
    }

    /**
     * Recalculate invoice balances for all allocations of a payment.
     * Called when a payment status changes (e.g., voided, posted).
     */
    public function recalculateInvoiceBalances(Payment $payment): void
    {
        $payment->load('allocations.invoice');

        foreach ($payment->allocations as $allocation) {
            $allocation->invoice->recalculatePayments();
        }
    }
}
