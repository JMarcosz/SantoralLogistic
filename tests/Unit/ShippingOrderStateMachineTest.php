<?php

namespace Tests\Unit;

use App\Enums\ShippingOrderStatus;
use App\Exceptions\InvalidShippingOrderStateTransitionException;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ServiceType;
use App\Models\ShippingOrder;
use App\Models\TransportMode;
use App\Services\ShippingOrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingOrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingOrderStateMachine $stateMachine;
    protected ShippingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateMachine = new ShippingOrderStateMachine();

        // Create required dependencies directly
        $customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'TEST001',
            'is_active' => true,
        ]);

        $originPort = Port::create([
            'code' => 'MIA',
            'name' => 'Miami International Airport',
            'country' => 'US',
            'type' => 'air',
            'is_active' => true,
        ]);

        $destPort = Port::create([
            'code' => 'SDQ',
            'name' => 'Las Americas Airport',
            'country' => 'DO',
            'type' => 'air',
            'is_active' => true,
        ]);

        $transportMode = TransportMode::create([
            'code' => 'AIR',
            'name' => 'Air Freight',
            'is_active' => true,
        ]);

        $serviceType = ServiceType::create([
            'code' => 'EXW',
            'name' => 'Ex Works',
            'is_active' => true,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_default' => true,
        ]);

        $this->order = ShippingOrder::create([
            'customer_id' => $customer->id,
            'origin_port_id' => $originPort->id,
            'destination_port_id' => $destPort->id,
            'transport_mode_id' => $transportMode->id,
            'service_type_id' => $serviceType->id,
            'currency_id' => $currency->id,
            'status' => 'draft',
        ]);
    }

    // ==================== Valid Transitions ====================

    public function test_can_book_draft_order(): void
    {
        $result = $this->stateMachine->book($this->order);

        $this->assertEquals(ShippingOrderStatus::Booked, $result->status);
    }

    public function test_can_start_transit_booked_order(): void
    {
        $this->order->update(['status' => 'booked']);

        $result = $this->stateMachine->startTransit($this->order);

        $this->assertEquals(ShippingOrderStatus::InTransit, $result->status);
        $this->assertNotNull($result->actual_departure_at);
    }

    public function test_can_arrive_in_transit_order(): void
    {
        $this->order->update(['status' => 'in_transit']);

        $result = $this->stateMachine->arrive($this->order);

        $this->assertEquals(ShippingOrderStatus::Arrived, $result->status);
        $this->assertNotNull($result->actual_arrival_at);
    }

    public function test_can_deliver_arrived_order(): void
    {
        $this->order->update(['status' => 'arrived']);

        $result = $this->stateMachine->deliver($this->order);

        $this->assertEquals(ShippingOrderStatus::Delivered, $result->status);
        $this->assertNotNull($result->delivery_date);
    }

    public function test_can_close_delivered_order(): void
    {
        $this->order->update(['status' => 'delivered']);

        $result = $this->stateMachine->close($this->order);

        $this->assertEquals(ShippingOrderStatus::Closed, $result->status);
    }

    public function test_can_cancel_draft_order(): void
    {
        $result = $this->stateMachine->cancel($this->order, 'Customer request');

        $this->assertEquals(ShippingOrderStatus::Cancelled, $result->status);
        $this->assertFalse($result->is_active);
        $this->assertStringContains('CANCELADO', $result->notes);
    }

    public function test_can_cancel_booked_order(): void
    {
        $this->order->update(['status' => 'booked']);

        $result = $this->stateMachine->cancel($this->order);

        $this->assertEquals(ShippingOrderStatus::Cancelled, $result->status);
    }

    // ==================== Invalid Transitions ====================

    public function test_cannot_book_already_booked_order(): void
    {
        $this->order->update(['status' => 'booked']);

        $this->expectException(InvalidShippingOrderStateTransitionException::class);

        $this->stateMachine->book($this->order);
    }

    public function test_cannot_start_transit_from_draft(): void
    {
        $this->expectException(InvalidShippingOrderStateTransitionException::class);

        $this->stateMachine->startTransit($this->order);
    }

    public function test_cannot_arrive_from_booked(): void
    {
        $this->order->update(['status' => 'booked']);

        $this->expectException(InvalidShippingOrderStateTransitionException::class);

        $this->stateMachine->arrive($this->order);
    }

    public function test_cannot_deliver_from_in_transit(): void
    {
        $this->order->update(['status' => 'in_transit']);

        $this->expectException(InvalidShippingOrderStateTransitionException::class);

        $this->stateMachine->deliver($this->order);
    }

    public function test_cannot_close_from_arrived(): void
    {
        $this->order->update(['status' => 'arrived']);

        $this->expectException(InvalidShippingOrderStateTransitionException::class);

        $this->stateMachine->close($this->order);
    }

    public function test_cannot_transition_closed_order(): void
    {
        $this->order->update(['status' => 'closed']);

        $this->expectException(InvalidShippingOrderStateTransitionException::class);

        $this->stateMachine->book($this->order);
    }

    public function test_cannot_transition_cancelled_order(): void
    {
        $this->order->update(['status' => 'cancelled']);

        $this->expectException(InvalidShippingOrderStateTransitionException::class);

        $this->stateMachine->book($this->order);
    }

    // ==================== Enum Tests ====================

    public function test_status_labels_are_correct(): void
    {
        $this->assertEquals('Borrador', ShippingOrderStatus::Draft->label());
        $this->assertEquals('Reservado', ShippingOrderStatus::Booked->label());
        $this->assertEquals('En Tránsito', ShippingOrderStatus::InTransit->label());
        $this->assertEquals('Llegado', ShippingOrderStatus::Arrived->label());
        $this->assertEquals('Entregado', ShippingOrderStatus::Delivered->label());
        $this->assertEquals('Cerrado', ShippingOrderStatus::Closed->label());
        $this->assertEquals('Cancelado', ShippingOrderStatus::Cancelled->label());
    }

    public function test_terminal_states_are_correct(): void
    {
        $this->assertFalse(ShippingOrderStatus::Draft->isTerminal());
        $this->assertFalse(ShippingOrderStatus::Booked->isTerminal());
        $this->assertFalse(ShippingOrderStatus::InTransit->isTerminal());
        $this->assertFalse(ShippingOrderStatus::Arrived->isTerminal());
        $this->assertFalse(ShippingOrderStatus::Delivered->isTerminal());
        $this->assertTrue(ShippingOrderStatus::Closed->isTerminal());
        $this->assertTrue(ShippingOrderStatus::Cancelled->isTerminal());
    }

    public function test_valid_transitions_are_correct(): void
    {
        $this->assertTrue(ShippingOrderStatus::Draft->canTransitionTo(ShippingOrderStatus::Booked));
        $this->assertTrue(ShippingOrderStatus::Draft->canTransitionTo(ShippingOrderStatus::Cancelled));
        $this->assertFalse(ShippingOrderStatus::Draft->canTransitionTo(ShippingOrderStatus::InTransit));

        $this->assertTrue(ShippingOrderStatus::Booked->canTransitionTo(ShippingOrderStatus::InTransit));
        $this->assertFalse(ShippingOrderStatus::Booked->canTransitionTo(ShippingOrderStatus::Delivered));

        $this->assertTrue(ShippingOrderStatus::InTransit->canTransitionTo(ShippingOrderStatus::Arrived));
        $this->assertFalse(ShippingOrderStatus::InTransit->canTransitionTo(ShippingOrderStatus::Cancelled));
    }

    // ==================== KPI Integration Tests ====================

    public function test_arrive_calculates_kpis_when_on_time(): void
    {
        // Set up order with planned arrival in the future
        $this->order->update([
            'status' => 'in_transit',
            'planned_arrival_at' => now()->addDays(2),
        ]);

        // Arrive on time (today, before planned)
        $result = $this->stateMachine->arrive($this->order);

        $this->assertEquals(ShippingOrderStatus::Arrived, $result->status);
        $this->assertTrue($result->delivered_on_time);
        $this->assertEquals(0, $result->delivery_delay_days);
    }

    public function test_arrive_calculates_kpis_when_late(): void
    {
        // Set up order with planned arrival in the past
        $this->order->update([
            'status' => 'in_transit',
            'planned_arrival_at' => now()->subDays(3),
        ]);

        // Arrive late (today, after planned)
        $result = $this->stateMachine->arrive($this->order);

        $this->assertEquals(ShippingOrderStatus::Arrived, $result->status);
        $this->assertFalse($result->delivered_on_time);
        $this->assertEquals(3, $result->delivery_delay_days);
    }

    public function test_deliver_preserves_kpis_calculated_on_arrival(): void
    {
        // Set up order that arrived on time
        $this->order->update([
            'status' => 'arrived',
            'planned_arrival_at' => now(),
            'actual_arrival_at' => now(),
            'delivered_on_time' => true,
            'delivery_delay_days' => 0,
        ]);

        $result = $this->stateMachine->deliver($this->order);

        $this->assertEquals(ShippingOrderStatus::Delivered, $result->status);
        $this->assertTrue($result->delivered_on_time);
        $this->assertEquals(0, $result->delivery_delay_days);
    }

    public function test_deliver_recalculates_kpis_if_needed(): void
    {
        // Set up order that has arrival dates but no KPIs yet
        $this->order->update([
            'status' => 'arrived',
            'planned_arrival_at' => now()->subDays(2),
            'actual_arrival_at' => now(),
        ]);

        $result = $this->stateMachine->deliver($this->order);

        $this->assertEquals(ShippingOrderStatus::Delivered, $result->status);
        $this->assertFalse($result->delivered_on_time);
        $this->assertEquals(2, $result->delivery_delay_days);
    }

    public function test_kpis_remain_null_when_planned_arrival_missing(): void
    {
        // Set up order without planned arrival
        $this->order->update([
            'status' => 'in_transit',
            'planned_arrival_at' => null,
        ]);

        $result = $this->stateMachine->arrive($this->order);

        $this->assertEquals(ShippingOrderStatus::Arrived, $result->status);
        $this->assertNull($result->delivered_on_time);
        $this->assertNull($result->delivery_delay_days);
    }

    /**
     * Helper to check string contains.
     */
    protected function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertTrue(str_contains($haystack, $needle), "String does not contain '{$needle}'");
    }
}
