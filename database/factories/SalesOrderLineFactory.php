<?php

namespace Database\Factories;

use App\Models\ProductService;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_service_id' => ProductService::factory(),
            'line_type' => 'service',
            'description' => $this->faker->sentence(3),
            'quantity' => $this->faker->randomFloat(2, 1, 50),
            'unit_price' => $this->faker->randomFloat(4, 10, 500),
            'unit_cost' => $this->faker->randomFloat(4, 5, 200),
            'discount_percent' => 0,
            'tax_rate' => 18.00,
            'line_total' => function (array $attributes) {
                return $attributes['quantity'] * $attributes['unit_price'];
            },
            'sort_order' => 0,
        ];
    }

    /**
     * State: product line (inventoriable).
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'line_type' => 'product',
            'product_service_id' => ProductService::factory()->product(),
        ]);
    }

    /**
     * State: service line.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'line_type' => 'service',
            'product_service_id' => ProductService::factory()->service(),
        ]);
    }
}
