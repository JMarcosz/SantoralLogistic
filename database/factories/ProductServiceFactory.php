<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ProductService;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductServiceFactory extends Factory
{
    protected $model = ProductService::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('PS-####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['service', 'product', 'fee']),
            'uom' => $this->faker->randomElement(['unit', 'kg', 'shipment', 'day']),
            'default_currency_id' => Currency::first()?->id ?? Currency::factory(),
            'default_unit_price' => $this->faker->randomFloat(4, 5, 500),
            'taxable' => true,
            'is_active' => true,
        ];
    }

    /**
     * State: product type (inventoriable).
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
            'uom' => 'unit',
        ]);
    }

    /**
     * State: service type.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
        ]);
    }

    /**
     * State: fee type.
     */
    public function fee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fee',
        ]);
    }
}
