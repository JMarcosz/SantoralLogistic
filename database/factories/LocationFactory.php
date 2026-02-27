<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'code' => strtoupper($this->faker->bothify('LOC-##-##')),
            'zone' => 'A',
            'type' => 'rack',
            'is_active' => true,
            'max_weight_kg' => 1000,
        ];
    }
}
