<?php

namespace Database\Factories;

use App\Models\WarehouseReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseReceiptLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_receipt_id' => WarehouseReceipt::factory(),
            'item_code' => $this->faker->bothify('SKU-####'),
            'description' => $this->faker->sentence(3),
            'expected_qty' => $this->faker->randomFloat(2, 1, 100),
            'received_qty' => 0,
            'uom' => 'PCS',
            'lot_number' => $this->faker->bothify('LOT-####'),
        ];
    }
}
