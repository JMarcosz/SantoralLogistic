<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PreInvoice;
use App\Models\PreInvoiceLine;
use Illuminate\Database\Seeder;

class PreInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test pre-invoices for development/testing.
     */
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please seed customers first.');
            return;
        }

        $statuses = ['draft', 'draft', 'issued', 'issued', 'issued', 'issued', 'issued', 'cancelled'];
        $currencies = ['USD', 'USD', 'DOP', 'USD'];

        $chargeTypes = [
            ['code' => 'FRT', 'description' => 'Ocean Freight', 'price' => 1500.00],
            ['code' => 'THC', 'description' => 'Terminal Handling Charge', 'price' => 350.00],
            ['code' => 'DOC', 'description' => 'Documentation Fee', 'price' => 75.00],
            ['code' => 'BL', 'description' => 'Bill of Lading Fee', 'price' => 50.00],
            ['code' => 'SEAL', 'description' => 'Container Seal', 'price' => 25.00],
            ['code' => 'INSP', 'description' => 'Inspection Fee', 'price' => 150.00],
            ['code' => 'DRAY', 'description' => 'Drayage / Local Transport', 'price' => 450.00],
            ['code' => 'CUST', 'description' => 'Customs Clearance', 'price' => 200.00],
            ['code' => 'HAND', 'description' => 'Handling Fee', 'price' => 85.00],
            ['code' => 'INS', 'description' => 'Cargo Insurance', 'price' => 275.00],
        ];

        $this->command->info('Creating 10 test pre-invoices...');

        for ($i = 1; $i <= 10; $i++) {
            $customer = $customers->random();
            $status = $statuses[array_rand($statuses)];
            $currency = $currencies[array_rand($currencies)];
            $issueDate = now()->subDays(rand(1, 60));
            $dueDate = $issueDate->copy()->addDays(30);

            // Create the pre-invoice
            $preInvoice = PreInvoice::create([
                'number' => sprintf('PI-%d-%06d', now()->year, $i),
                'customer_id' => $customer->id,
                'shipping_order_id' => null, // Manual pre-invoices for testing
                'currency_code' => $currency,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => $status,
                'subtotal_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'notes' => "Test pre-invoice #{$i} for {$customer->name}",
            ]);

            // Add 2-5 random line items
            $numLines = rand(2, 5);
            $selectedCharges = collect($chargeTypes)->random($numLines);
            $subtotal = 0;
            $totalTax = 0;

            foreach ($selectedCharges as $sortOrder => $charge) {
                $qty = rand(1, 3);
                $unitPrice = $charge['price'] * (rand(80, 120) / 100); // +/- 20% variation
                $amount = round($qty * $unitPrice, 2);
                $taxAmount = round($amount * 0.18, 2); // 18% ITBIS

                PreInvoiceLine::create([
                    'pre_invoice_id' => $preInvoice->id,
                    'charge_id' => null,
                    'code' => $charge['code'],
                    'description' => $charge['description'],
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'amount' => $amount,
                    'tax_amount' => $taxAmount,
                    'currency_code' => $currency,
                    'sort_order' => $sortOrder + 1,
                ]);

                $subtotal += $amount;
                $totalTax += $taxAmount;
            }

            // Update totals
            $preInvoice->update([
                'subtotal_amount' => $subtotal,
                'tax_amount' => $totalTax,
                'total_amount' => $subtotal + $totalTax,
            ]);

            $this->command->line("  Created: {$preInvoice->number} ({$status}) - {$currency} " . number_format($preInvoice->total_amount, 2));
        }

        $this->command->info('Pre-invoice seeding completed!');
    }
}
