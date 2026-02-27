<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransportMode>
 */
class TransportModeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(1),
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'supports_awb' => $this->faker->boolean(),
            'supports_bl' => $this->faker->boolean(),
            'supports_pod' => $this->faker->boolean(),
            'is_active' => true,
        ];
    }
}
