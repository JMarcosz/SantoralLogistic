<?php

namespace App\Services;

use App\Models\PreInvoice;
use App\Models\ShippingOrder;
use App\Models\Charge;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Support\Str;

class PreInvoiceService
{
    /**
     * Create a PreInvoice from a ShippingOrder.
     */
    public function createFromShippingOrder(ShippingOrder $order): PreInvoice
    {
        $this->ensureOrderFacturable($order);

        return DB::transaction(function () use ($order) {
            $number = $this->generateNextNumber();

            // Create Header
            $preInvoice = PreInvoice::create([
                'number' => $number,
                'customer_id' => $order->customer_id,
                'shipping_order_id' => $order->id,
                'currency_code' => $order->currency->code, // Get code from Currency relationship
                'issue_date' => now(),
                'due_date' => now()->addDays(30), // Default policy
                'status' => 'draft',
                'notes' => 'Generada desde Orden de Envío ' . $order->order_number,
            ]);

            // Process Charges
            $subtotal = 0;
            $totalTax = 0;

            // Eager load taxes to avoid N+1
            foreach ($order->charges()->with('taxes')->get() as $charge) {
                // Calculate line tax amount
                // Logic: sum of (tax_amount in pivot) OR calculate if dynamic
                // Just assuming pivot has the value for fixed tax amounts,
                // BUT usually pivot tax_amount is calculated from rate * charge amount.
                // AC-1 said: `tax_amount` in pivot.
                // Use helper to calculate if pivot is missing or just sum pivot?
                // For now, assume pivot has correct values if relation exists.
                // If no taxes linked, tax is 0.

                $lineTax = $charge->taxes->sum('pivot.tax_amount');

                $preInvoice->lines()->create([
                    'charge_id' => $charge->id,
                    'code' => $charge->code,
                    'description' => $charge->description,
                    'qty' => $charge->qty,
                    'unit_price' => $charge->unit_price,
                    'amount' => $charge->amount,
                    'tax_amount' => $lineTax,
                    'currency_code' => $charge->currency_code,
                    'sort_order' => $charge->sort_order,
                ]);

                $subtotal += $charge->amount;
                $totalTax += $lineTax;
            }

            // Update Totals
            $preInvoice->update([
                'subtotal_amount' => $subtotal,
                'tax_amount' => $totalTax,
                'total_amount' => $subtotal + $totalTax,
            ]);

            return $preInvoice;
        });
    }

    /**
     * Create a PreInvoice manually (without a ShippingOrder).
     */
    public function createManual(array $data): PreInvoice
    {
        return DB::transaction(function () use ($data) {
            $number = $this->generateNextNumber();

            // Calculate totals from lines
            $subtotal = 0;
            $totalTax = 0;

            foreach ($data['lines'] as $line) {
                $lineAmount = $line['qty'] * $line['unit_price'];
                $subtotal += $lineAmount;

                $isTaxable = $line['is_taxable'] ?? true;
                $taxRate = $line['tax_rate'] ?? 0.18;

                if ($isTaxable) {
                    $totalTax += $lineAmount * $taxRate;
                }
            }

            // Create Header
            $preInvoice = PreInvoice::create([
                'number' => $number,
                'customer_id' => $data['customer_id'],
                'shipping_order_id' => null,
                'currency_code' => $data['currency_code'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'external_ref' => $data['external_ref'] ?? null,
                'subtotal_amount' => $subtotal,
                'tax_amount' => $totalTax,
                'total_amount' => $subtotal + $totalTax,
            ]);

            // Create Lines
            foreach ($data['lines'] as $index => $line) {
                $lineAmount = $line['qty'] * $line['unit_price'];
                $isTaxable = $line['is_taxable'] ?? true;
                $taxRate = $line['tax_rate'] ?? 0.18;
                $taxAmount = $isTaxable ? ($lineAmount * $taxRate) : 0;

                $preInvoice->lines()->create([
                    'charge_id' => null,
                    'code' => $line['code'],
                    'description' => $line['description'],
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'amount' => $lineAmount,
                    'tax_amount' => $taxAmount,
                    'is_taxable' => $isTaxable,
                    'tax_rate' => $taxRate,
                    'currency_code' => $data['currency_code'],
                    'sort_order' => $index + 1,
                ]);
            }

            return $preInvoice;
        });
    }

    protected function ensureOrderFacturable(ShippingOrder $order): void
    {
        // Must be Arrived, Delivered or Closed
        // Check Enum status
        if (!$order->status->isTerminal() && !in_array($order->status->value, ['arrived', 'delivered'])) {
            // Allow Arrived, Delivered, Closed.
            // Draft, Booked, InTransit -> Not billable yet?
            // User said: "ej. delivered or closed".
            // I'll stick to strict: Delivered or Closed.
            // Actually, usually you invoice *before* delivery sometimes.
            // Let's implement Delivered or Closed or Arrived.
        }

        // Simplified check:
        $validStatuses = ['arrived', 'delivered', 'closed'];
        if (!in_array($order->status->value, $validStatuses)) {
            throw new InvalidArgumentException("La orden debe estar en estado Arrived, Delivered o Closed para facturar.");
        }

        // Check for duplicate active PreInvoice
        if ($order->preInvoices()->where('status', '!=', 'cancelled')->exists()) {
            throw new InvalidArgumentException("Ya existe una Pre-Factura activa para esta orden.");
        }
    }

    protected function generateNextNumber(): string
    {
        // Format: PI-YYYY-######
        $year = now()->year;
        $prefix = "PI-{$year}-";

        // Find last number with lock to prevent race conditions
        $last = PreInvoice::where('number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderBy('number', 'desc')
            ->first();

        if (!$last) {
            return $prefix . '000001';
        }

        // Extract sequence
        $sequence = (int) Str::after($last->number, $prefix);
        $next = $sequence + 1;

        return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
