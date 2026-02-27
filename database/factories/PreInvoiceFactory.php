<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\ShippingOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreInvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'PI-' . $this->faker->year() . '-' . $this->faker->unique()->numberBetween(100000, 999999),
            'customer_id' => Customer::factory(),
            'shipping_order_id' => null,
            'currency_code' => 'DOP',
            'issue_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => 'issued',
            'subtotal_amount' => $this->faker->randomFloat(2, 100, 10000),
            'tax_amount' => function (array $attributes) {
                return round($attributes['subtotal_amount'] * 0.18, 2);
            },
            'total_amount' => function (array $attributes) {
                return round($attributes['subtotal_amount'] + $attributes['tax_amount'], 2);
            },
            'paid_amount' => 0.00,
            'balance' => function (array $attributes) {
                return $attributes['total_amount'];
            },
            'paid_at' => null,
            'notes' => $this->faker->optional()->sentence(),
            'external_ref' => null,
            'exported_at' => null,
            'export_reference' => null,
            'invoiced_at' => null,
        ];
    }

    /**
     * Indicate that the pre-invoice is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the pre-invoice is issued.
     */
    public function issued(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'issued',
        ]);
    }

    /**
     * Indicate that the pre-invoice has been invoiced.
     */
    public function invoiced(): static
    {
        return $this->state(fn(array $attributes) => [
            'invoiced_at' => now(),
        ]);
    }

    /**
     * Indicate that the pre-invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'paid',
            'paid_amount' => $attributes['total_amount'],
            'balance' => 0.00,
            'paid_at' => now(),
        ]);
    }
}
