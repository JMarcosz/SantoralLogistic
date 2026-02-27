<?php

namespace Database\Factories;

use App\Models\FiscalSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FiscalSequence>
 */
class FiscalSequenceFactory extends Factory
{
    protected $model = FiscalSequence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ncfType = fake()->randomElement(['B01', 'B02', 'B14', 'B15', 'B16']);
        $series = fake()->optional(0.7)->numerify('00#');
        $startNumber = fake()->numberBetween(1, 900);

        return [
            'ncf_type' => $ncfType,
            'series' => $series,
            'ncf_from' => sprintf('%s%s%011d', $ncfType, $series ?? '', $startNumber),
            'ncf_to' => sprintf('%s%s%011d', $ncfType, $series ?? '', $startNumber + 1000),
            'current_ncf' => null,
            'valid_from' => now()->subMonths(rand(1, 6)),
            'valid_to' => now()->addMonths(rand(6, 18)),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the sequence is in use (has current_ncf).
     */
    public function inUse(): static
    {
        return $this->state(function (array $attributes) {
            preg_match('/(\d+)$/', $attributes['ncf_from'], $matches);
            $startNum = (int) $matches[1];
            $currentNum = $startNum + rand(100, 500);

            $prefix = substr($attributes['ncf_from'], 0, -11);

            return [
                'current_ncf' => $prefix . str_pad((string) $currentNum, 11, '0', STR_PAD_LEFT),
            ];
        });
    }

    /**
     * Indicate that the sequence is exhausted.
     */
    public function exhausted(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'current_ncf' => $attributes['ncf_to'],
            ];
        });
    }

    /**
     * Indicate that the sequence is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the sequence is expiring soon.
     */
    public function expiringSoon(int $days = 10): static
    {
        return $this->state(fn(array $attributes) => [
            'valid_to' => now()->addDays($days),
        ]);
    }
}
