<?php

namespace Database\Factories;

use App\Enums\WarehouseReceiptStatus;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'customer_id' => Customer::factory(),
            'receipt_number' => $this->faker->unique()->numerify('WR-#####'),
            'status' => WarehouseReceiptStatus::Draft,
            'reference' => $this->faker->bothify('REF-####'),
            'expected_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }
}
