<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'customer_id' => Customer::factory(),
            'location_id' => Location::factory(),
            'warehouse_receipt_id' => WarehouseReceipt::factory(),
            'warehouse_receipt_line_id' => null, // Optional in factory
            'item_code' => $this->faker->bothify('SKU-####'),
            'description' => $this->faker->sentence(3),
            'qty' => $this->faker->randomFloat(2, 1, 100),
            'uom' => 'PCS',
            'lot_number' => $this->faker->bothify('LOT-####'),
            'received_at' => now(),
        ];
    }
}
