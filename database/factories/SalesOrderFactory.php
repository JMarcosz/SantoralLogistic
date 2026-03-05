<?php

namespace Database\Factories;

use App\Enums\SalesOrderStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'PED-' . date('Y') . '-' . $this->faker->unique()->numberBetween(100000, 999999),
            'quote_id' => null,
            'customer_id' => Customer::factory(),
            'contact_id' => null,
            'currency_id' => Currency::first()?->id ?? Currency::factory(),
            'status' => SalesOrderStatus::Draft,
            'subtotal' => $this->faker->randomFloat(4, 100, 10000),
            'tax_amount' => $this->faker->randomFloat(4, 0, 1000),
            'total_amount' => function (array $attributes) {
                return $attributes['subtotal'] + $attributes['tax_amount'];
            },
            'created_by' => User::factory(),
        ];
    }

    /**
     * State: confirmed order.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SalesOrderStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }
}
