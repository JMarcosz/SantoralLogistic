<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'inbound',
            'customer_id' => Customer::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'payment_date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency_code' => 'DOP',
            'exchange_rate' => 1,
            'base_amount' => fn(array $attributes) => $attributes['amount'] * $attributes['exchange_rate'],
            'isr_withholding_amount' => 0,
            'itbis_withholding_amount' => 0,
            'net_amount' => fn(array $attributes) => $attributes['amount'],
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the payment is posted.
     */
    public function posted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the payment is voided.
     */
    public function voided(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'voided',
            'voided_at' => now(),
            'voided_by' => User::factory(),
            'void_reason' => 'Test void reason',
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
