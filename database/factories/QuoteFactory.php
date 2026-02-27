<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ServiceType;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_number' => $this->faker->unique()->bothify('Q-####-????'),
            'customer_id' => Customer::factory(),
            'contact_id' => Contact::factory(),
            'origin_port_id' => Port::factory(),
            'destination_port_id' => Port::factory(),
            'transport_mode_id' => TransportMode::factory(),
            'service_type_id' => ServiceType::factory(),
            'currency_id' => Currency::factory(),
            'status' => QuoteStatus::Draft,
            'total_pieces' => $this->faker->numberBetween(1, 100),
            'total_weight_kg' => $this->faker->randomFloat(2, 100, 5000),
            'total_volume_cbm' => $this->faker->randomFloat(2, 1, 50),
            'chargeable_weight_kg' => $this->faker->randomFloat(2, 100, 5000),
            'subtotal' => $this->faker->randomFloat(2, 100, 10000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 1000),
            'total_amount' => function (array $attributes) {
                return $attributes['subtotal'] + $attributes['tax_amount'];
            },
            'valid_until' => $this->faker->dateTimeBetween('now', '+30 days'),
            'created_by' => User::factory(),
            'sales_rep_id' => User::factory(),
        ];
    }
}
