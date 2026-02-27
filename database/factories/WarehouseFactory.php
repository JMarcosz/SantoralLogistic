<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Warehouse',
            'code' => strtoupper($this->faker->unique()->lexify('WH-?????')), // More chars to avoid collision
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'country' => $this->faker->country,
            'is_active' => true,
        ];
    }
}
