<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Port>
 */
class PortFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->regexify('[A-Z]{3}'),
            'name' => $this->faker->city() . ' Port',
            'country' => $this->faker->countryCode(),
            'city' => $this->faker->city(),
            'type' => $this->faker->randomElement(['ocean', 'air', 'ground']),
            'is_active' => true,
        ];
    }
}
