<?php

namespace Database\Factories;

use App\Enums\ShippingOrderStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ServiceType;
use App\Models\ShippingOrder;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingOrderFactory extends Factory
{
    protected $model = ShippingOrder::class;

    public function definition(): array
    {
        return [
            'order_number' => 'SO-' . date('Y') . '-' . $this->faker->unique()->numberBetween(100000, 999999),
            'quote_id' => null,
            'customer_id' => Customer::factory(),
            'contact_id' => null, // Optional
            'shipper_id' => Customer::factory(),
            'consignee_id' => Customer::factory(),
            'origin_port_id' => Port::first()?->id ?? Port::create(['code' => 'ORG', 'name' => 'Origin Port', 'country' => 'US', 'type' => 'ocean'])->id,
            'destination_port_id' => Port::first()?->id ?? Port::create(['code' => 'DST', 'name' => 'Dest Port', 'country' => 'DO', 'type' => 'ocean'])->id,
            'transport_mode_id' => TransportMode::first()?->id ?? TransportMode::create(['code' => 'SEA', 'name' => 'Sea'])->id,
            'service_type_id' => ServiceType::first()?->id ?? ServiceType::create(['code' => 'FCL', 'name' => 'FCL'])->id,
            'currency_id' => Currency::first()?->id ?? Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'])->id,
            'total_amount' => $this->faker->randomFloat(4, 100, 10000),
            'total_pieces' => $this->faker->numberBetween(1, 100),
            'total_weight_kg' => $this->faker->randomFloat(3, 10, 5000),
            'total_volume_cbm' => $this->faker->randomFloat(3, 1, 100),
            'status' => ShippingOrderStatus::Draft,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
