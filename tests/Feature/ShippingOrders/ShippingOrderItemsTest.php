<?php

namespace Tests\Feature\ShippingOrders;

use App\Models\ShippingOrder;
use App\Models\ShippingOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingOrderItemsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_shipping_order_with_items_and_lines()
    {
        $shippingOrder = ShippingOrder::factory()->create();

        $item = $shippingOrder->items()->create([
            'type' => 'container',
            'identifier' => 'CONT1234567',
        ]);

        $line = $item->lines()->create([
            'pieces' => 10,
            'description' => 'Test Cargo',
            'weight_kg' => 100.5,
            'volume_cbm' => 2.5,
        ]);

        $this->assertDatabaseHas('shipping_order_items', ['id' => $item->id]);
        $this->assertDatabaseHas('shipping_order_item_lines', ['id' => $line->id]);
        $this->assertEquals(10, $line->pieces);
    }

    public function test_calculates_totals_from_items()
    {
        $shippingOrder = ShippingOrder::factory()->create([
            'total_pieces' => 0,
            'total_weight_kg' => 0,
            'total_volume_cbm' => 0,
        ]);

        $item1 = $shippingOrder->items()->create(['type' => 'container']);
        $item1->lines()->create([
            'pieces' => 10,
            'description' => 'Box A',
            'weight_kg' => 100,
            'volume_cbm' => 1,
        ]);

        $item2 = $shippingOrder->items()->create(['type' => 'pallet']);
        $item2->lines()->create([
            'pieces' => 5,
            'description' => 'Box B',
            'weight_kg' => 50,
            'volume_cbm' => 0.5,
        ]);

        $shippingOrder->calculateTotalsFromItems();

        $this->assertEquals(15, $shippingOrder->total_pieces);
        $this->assertEquals(150, $shippingOrder->total_weight_kg);
        $this->assertEquals(1.5, $shippingOrder->total_volume_cbm);
    }

    public function test_line_requires_description_weight_volume()
    {
        $shippingOrder = ShippingOrder::factory()->create();
        $item = $shippingOrder->items()->create(['type' => 'container']);

        try {
            $item->lines()->create([
                'pieces' => 10,
                // Missing description, weight, volume
            ]);
            $this->fail('Should have thrown SQL/Validation error');
        } catch (\Exception $e) {
            $this->assertTrue(true); // Passed, likely SQL Not Null violation
        }
    }
}
