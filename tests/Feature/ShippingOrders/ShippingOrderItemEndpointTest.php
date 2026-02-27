<?php

namespace Tests\Feature\ShippingOrders;

use App\Models\ShippingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingOrderItemEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_add_item_with_lines()
    {
        $shippingOrder = ShippingOrder::factory()->create();

        $payload = [
            'type' => 'container',
            'identifier' => 'CNTR123',
            'lines' => [
                [
                    'pieces' => 10,
                    'description' => 'Electronics',
                    'weight_kg' => 500,
                    'volume_cbm' => 20,
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('shipping-orders.items.store', $shippingOrder), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('shipping_order_items', [
            'shipping_order_id' => $shippingOrder->id,
            'identifier' => 'CNTR123',
        ]);

        $this->assertDatabaseHas('shipping_order_item_lines', [
            'description' => 'Electronics',
            'weight_kg' => 500,
        ]);

        $shippingOrder->refresh();
        $this->assertEquals(10, $shippingOrder->total_pieces);
        $this->assertEquals(500, $shippingOrder->total_weight_kg);
    }

    public function test_validation_fails_on_missing_fields()
    {
        $shippingOrder = ShippingOrder::factory()->create();

        $payload = [
            'type' => 'container',
            'lines' => [
                [
                    'pieces' => 10,
                    // Missing description, weight, volume
                ]
            ]
        ];

        $response = $this->actingAs($this->user)
            ->post(route('shipping-orders.items.store', $shippingOrder), $payload);

        $response->assertSessionHasErrors(['lines.0.description', 'lines.0.weight_kg']);
    }

    public function test_can_delete_item()
    {
        $shippingOrder = ShippingOrder::factory()->create(['total_weight_kg' => 500]);
        $item = $shippingOrder->items()->create(['type' => 'container']);
        $item->lines()->create([
            'pieces' => 10,
            'description' => 'Stuff',
            'weight_kg' => 500,
            'volume_cbm' => 10,
        ]);

        $this->assertEquals(1, $shippingOrder->items()->count());

        $response = $this->actingAs($this->user)
            ->delete(route('shipping-orders.items.destroy', [$shippingOrder, $item]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('shipping_order_items', ['id' => $item->id]);

        $shippingOrder->refresh();
        $this->assertEquals(0, $shippingOrder->total_weight_kg);
    }
}
