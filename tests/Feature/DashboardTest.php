<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Quote;
use App\Models\ShippingOrder;
use App\Models\InventoryItem;
use App\Models\WarehouseReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles/permissions
        $this->seed(\Database\Seeders\PermissionSeeder::class);
    }

    public function test_dashboard_loads_with_metrics()
    {
        $user = User::factory()->create();

        // Create dependencies
        $customer = \App\Models\Customer::create(['name' => 'Cust', 'code' => 'C1', 'is_active' => true]);
        $portA = \App\Models\Port::create(['name' => 'PA', 'code' => 'PA', 'country' => 'US', 'type' => 'ocean', 'is_active' => true]);
        $portB = \App\Models\Port::create(['name' => 'PB', 'code' => 'PB', 'country' => 'DO', 'type' => 'ocean', 'is_active' => true]);
        $mode = \App\Models\TransportMode::create(['name' => 'Sea', 'code' => 'SEA', 'is_active' => true]);
        $service = \App\Models\ServiceType::create(['name' => 'FCL', 'code' => 'FCL', 'is_active' => true]);
        $currency = \App\Models\Currency::create(['name' => 'USD', 'code' => 'USD', 'symbol' => '$']);

        // 1. In Transit Shipping Order
        ShippingOrder::create([
            'order_number' => 'SO-001',
            'status' => 'in_transit',
            'customer_id' => $customer->id,
            'origin_port_id' => $portA->id,
            'destination_port_id' => $portB->id,
            'transport_mode_id' => $mode->id,
            'service_type_id' => $service->id,
            'currency_id' => $currency->id,
            'is_active' => true,
        ]);

        // Since ShippingOrder factory exists (likely), let's use it if available, else manual.
        // Assuming factories are not fully reliable given previous issues, let's try manual minimal or expect 0 if strict.

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('dashboard')
                ->has('kpis', 6)
                ->has('alerts')
                ->has('workQueue')
        );
    }
}
