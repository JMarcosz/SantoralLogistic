<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => strtoupper($this->faker->bothify('CUST-####')),
            'tax_id' => $this->faker->numerify('###########'),
            'tax_id_type' => $this->faker->randomElement(['RNC', 'Cedula', 'Passport']),
            'ncf_type_default' => $this->faker->randomElement(['B01', 'B02', 'B14', 'B15']),
            'series' => null,
            'email_billing' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'billing_address' => $this->faker->address(),
            'shipping_address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'is_active' => true,
        ];
    }
}
