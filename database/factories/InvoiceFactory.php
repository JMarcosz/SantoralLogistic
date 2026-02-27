<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PreInvoice;
use App\Models\ShippingOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 10000);
        $tax = round($subtotal * 0.18, 2);
        $total = round($subtotal + $tax, 2);

        return [
            'number' => 'INV-' . $this->faker->year() . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'ncf' => 'B' . $this->faker->randomElement(['01', '02', '14', '15']) . '-' . str_pad($this->faker->unique()->numberBetween(1, 999999999), 11, '0', STR_PAD_LEFT),
            'ncf_type' => $this->faker->randomElement(['B01', 'B02', 'B14', 'B15']),
            'customer_id' => Customer::factory(),
            'pre_invoice_id' => null,
            'shipping_order_id' => null,
            'currency_code' => 'DOP',
            'issue_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => 'issued',
            'payment_status' => 'pending',
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'amount_paid' => 0,
            'balance' => $total,
            'taxable_amount' => $subtotal,
            'exempt_amount' => 0.00,
            'notes' => $this->faker->optional()->sentence(),
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    /**
     * Indicate that the invoice is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the invoice is issued.
     */
    public function issued(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'issued',
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }
}
