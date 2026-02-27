<?php

namespace Tests\Feature;

use App\Enums\ShippingOrderStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ServiceType;
use App\Models\ShippingOrder;
use App\Models\TransportMode;
use App\Models\User;
use App\Services\ShippingOrderShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShippingOrderShipmentTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingOrderShipmentService $service;
    protected Customer $customer;
    protected Customer $shipper;
    protected Customer $consignee;
    protected Port $originPort;
    protected Port $destPort;
    protected TransportMode $oceanMode;
    protected TransportMode $airMode;
    protected ServiceType $serviceType;
    protected Currency $currency;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ShippingOrderShipmentService::class);

        // Create test data
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CUST001',
            'is_active' => true,
        ]);

        $this->shipper = Customer::create([
            'name' => 'Test Shipper',
            'code' => 'SHIP001',
            'is_active' => true,
        ]);

        $this->consignee = Customer::create([
            'name' => 'Test Consignee',
            'code' => 'CONS001',
            'is_active' => true,
        ]);

        $this->originPort = Port::create([
            'code' => 'MIA',
            'name' => 'Miami',
            'country' => 'US',
            'type' => 'ocean',
            'is_active' => true,
        ]);

        $this->destPort = Port::create([
            'code' => 'SDQ',
            'name' => 'Santo Domingo',
            'country' => 'DO',
            'type' => 'ocean',
            'is_active' => true,
        ]);

        $this->oceanMode = TransportMode::create([
            'code' => 'OCEAN',
            'name' => 'Ocean Freight',
            'is_active' => true,
        ]);

        $this->airMode = TransportMode::create([
            'code' => 'AIR',
            'name' => 'Air Freight',
            'is_active' => true,
        ]);

        $this->serviceType = ServiceType::create([
            'code' => 'FCL',
            'name' => 'Full Container Load',
            'is_active' => true,
        ]);

        $this->currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_default' => true,
        ]);

        $this->user = User::factory()->create();
    }

    protected function createOrder(TransportMode $mode, ?Customer $shipper = null, ?Customer $consignee = null): ShippingOrder
    {
        return ShippingOrder::create([
            'customer_id' => $this->customer->id,
            'shipper_id' => $shipper?->id,
            'consignee_id' => $consignee?->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destPort->id,
            'transport_mode_id' => $mode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => ShippingOrderStatus::Draft->value,
        ]);
    }

    // ========== Shipper/Consignee Tests ==========

    public function test_can_create_shipping_order_with_shipper_and_consignee(): void
    {
        $order = $this->createOrder($this->oceanMode, $this->shipper, $this->consignee);

        $this->assertNotNull($order->id);
        $this->assertEquals($this->shipper->id, $order->shipper_id);
        $this->assertEquals($this->consignee->id, $order->consignee_id);

        // Verify relationships load
        $order->load(['shipper', 'consignee']);
        $this->assertEquals('Test Shipper', $order->shipper->name);
        $this->assertEquals('Test Consignee', $order->consignee->name);
    }

    public function test_shipper_and_consignee_are_optional(): void
    {
        $order = $this->createOrder($this->oceanMode);

        $this->assertNotNull($order->id);
        $this->assertNull($order->shipper_id);
        $this->assertNull($order->consignee_id);
    }

    // ========== Ocean Shipment Tests ==========

    public function test_can_create_ocean_shipment_for_ocean_mode(): void
    {
        $order = $this->createOrder($this->oceanMode);

        $oceanShipment = $this->service->upsertOceanDetails($order, [
            'mbl_number' => 'MBL123456',
            'hbl_number' => 'HBL789012',
            'carrier_name' => 'Maersk',
            'vessel_name' => 'MSC Atlanta',
            'voyage_number' => 'V123',
        ]);

        $this->assertNotNull($oceanShipment->id);
        $this->assertEquals('MBL123456', $oceanShipment->mbl_number);
        $this->assertEquals('Maersk', $oceanShipment->carrier_name);
        $this->assertEquals($order->id, $oceanShipment->shipping_order_id);
    }

    public function test_cannot_create_ocean_shipment_for_air_mode(): void
    {
        $order = $this->createOrder($this->airMode);

        $this->expectException(ValidationException::class);

        $this->service->upsertOceanDetails($order, [
            'mbl_number' => 'MBL123',
        ]);
    }

    // ========== Air Shipment Tests ==========

    public function test_can_create_air_shipment_for_air_mode(): void
    {
        $order = $this->createOrder($this->airMode);

        $airShipment = $this->service->upsertAirDetails($order, [
            'mawb_number' => 'MAWB123456',
            'hawb_number' => 'HAWB789012',
            'airline_name' => 'American Airlines',
            'flight_number' => 'AA1234',
        ]);

        $this->assertNotNull($airShipment->id);
        $this->assertEquals('MAWB123456', $airShipment->mawb_number);
        $this->assertEquals('American Airlines', $airShipment->airline_name);
        $this->assertEquals($order->id, $airShipment->shipping_order_id);
    }

    public function test_cannot_create_air_shipment_for_ocean_mode(): void
    {
        $order = $this->createOrder($this->oceanMode);

        $this->expectException(ValidationException::class);

        $this->service->upsertAirDetails($order, [
            'mawb_number' => 'MAWB123',
        ]);
    }

    // ========== Exclusivity Tests ==========

    public function test_cannot_have_both_air_and_ocean_shipments(): void
    {
        // Create ocean order with ocean shipment
        $order = $this->createOrder($this->oceanMode);
        $this->service->upsertOceanDetails($order, ['mbl_number' => 'MBL123']);

        // Try to create air shipment - should fail
        $this->expectException(ValidationException::class);
        $this->service->upsertAirDetails($order, ['mawb_number' => 'MAWB123']);
    }

    // ========== Cascade Delete Tests ==========

    public function test_cascade_deletes_ocean_shipment_when_order_deleted(): void
    {
        $order = $this->createOrder($this->oceanMode);
        $oceanShipment = $this->service->upsertOceanDetails($order, ['mbl_number' => 'MBL123']);

        $oceanShipmentId = $oceanShipment->id;

        // Force delete the order
        $order->forceDelete();

        // Verify ocean shipment is also deleted
        $this->assertDatabaseMissing('ocean_shipments', ['id' => $oceanShipmentId]);
    }

    public function test_cascade_deletes_air_shipment_when_order_deleted(): void
    {
        $order = $this->createOrder($this->airMode);
        $airShipment = $this->service->upsertAirDetails($order, ['mawb_number' => 'MAWB123']);

        $airShipmentId = $airShipment->id;

        // Force delete the order
        $order->forceDelete();

        // Verify air shipment is also deleted
        $this->assertDatabaseMissing('air_shipments', ['id' => $airShipmentId]);
    }
}
