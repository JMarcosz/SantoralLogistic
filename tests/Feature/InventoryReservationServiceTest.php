<?php

use App\Enums\ShippingOrderStatus;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Port;
use App\Models\ServiceType;
use App\Models\ShippingOrder;
use App\Models\TransportMode;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user and authenticate
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    $this->actingAs($this->user);

    // Create a currency
    $this->currency = Currency::create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'is_default' => true,
    ]);

    // Create a customer
    $this->customer = Customer::create([
        'name' => 'Test Customer',
        'code' => 'CUST001',
    ]);

    // Create a warehouse
    $this->warehouse = Warehouse::create([
        'name' => 'Test Warehouse',
        'code' => 'WH001',
        'is_active' => true,
    ]);

    // Create required entities for ShippingOrder
    $this->port = Port::create(['name' => 'Test Port', 'code' => 'TST', 'country' => 'DO', 'type' => 'ocean']);
    $this->transportMode = TransportMode::create(['name' => 'Ocean', 'code' => 'OCEAN']);
    $this->serviceType = ServiceType::create(['name' => 'FCL', 'code' => 'FCL']);

    // Create inventory items
    $this->inventoryItem1 = InventoryItem::create([
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'item_code' => 'SKU-001',
        'description' => 'Test Product 1',
        'qty' => 100,
        'uom' => 'PCS',
        'received_at' => now()->subDays(5),
    ]);

    $this->inventoryItem2 = InventoryItem::create([
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'item_code' => 'SKU-001',
        'description' => 'Test Product 1 - Batch 2',
        'qty' => 50,
        'uom' => 'PCS',
        'received_at' => now()->subDays(2),
    ]);

    // Create a shipping order in booked status (required for reservation)
    $this->shippingOrder = ShippingOrder::create([
        'order_number' => 'SO-TEST-001',
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'origin_port_id' => $this->port->id,
        'destination_port_id' => $this->port->id,
        'transport_mode_id' => $this->transportMode->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ShippingOrderStatus::Booked,
        'total_amount' => 0,
    ]);

    $this->service = app(InventoryReservationService::class);
});

test('can reserve inventory for a shipping order', function () {
    $reservations = $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 30],
    ]);

    expect($reservations)->toHaveCount(1);
    expect((float) $reservations[0]->qty_reserved)->toBe(30.0);
    expect($reservations[0]->created_by)->toBe($this->user->id);
    expect($reservations[0]->shipping_order_id)->toBe($this->shippingOrder->id);
});

test('reserves from oldest inventory first (FIFO)', function () {
    $reservations = $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 120], // More than first item has
    ]);

    // Should create 2 reservations: 100 from item1, 20 from item2
    expect($reservations)->toHaveCount(2);
    expect($reservations[0]->inventory_item_id)->toBe($this->inventoryItem1->id);
    expect((float) $reservations[0]->qty_reserved)->toBe(100.0);
    expect($reservations[1]->inventory_item_id)->toBe($this->inventoryItem2->id);
    expect((float) $reservations[1]->qty_reserved)->toBe(20.0);
});

test('calculates available quantity correctly after reservation', function () {
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 30],
    ]);

    $this->inventoryItem1->refresh();
    expect($this->inventoryItem1->availableQuantity())->toBe(70.0);
});

test('throws exception when requesting more than available', function () {
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 200], // Only 150 total available
    ]);
})->throws(\InvalidArgumentException::class);

test('can release all reservations for a shipping order', function () {
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 50],
    ]);

    $count = $this->service->releaseReservationsForShippingOrder($this->shippingOrder);

    expect($count)->toBe(1);

    // Verify reservation is soft deleted
    expect(InventoryReservation::count())->toBe(0);
    expect(InventoryReservation::withTrashed()->count())->toBe(1);

    // Verify deleted_by is set
    $deletedReservation = InventoryReservation::withTrashed()->first();
    expect($deletedReservation->deleted_by)->toBe($this->user->id);
});

test('released inventory becomes available again', function () {
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 50],
    ]);

    $this->inventoryItem1->refresh();
    expect($this->inventoryItem1->availableQuantity())->toBe(50.0);

    $this->service->releaseReservationsForShippingOrder($this->shippingOrder);

    $this->inventoryItem1->refresh();
    expect($this->inventoryItem1->availableQuantity())->toBe(100.0);
});

test('cannot reserve for closed shipping order', function () {
    $closedOrder = ShippingOrder::create([
        'order_number' => 'SO-TEST-002',
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'origin_port_id' => $this->port->id,
        'destination_port_id' => $this->port->id,
        'transport_mode_id' => $this->transportMode->id,
        'service_type_id' => $this->serviceType->id,
        'status' => ShippingOrderStatus::Closed,
        'total_amount' => 0,
    ]);

    $this->service->reserveForShippingOrder($closedOrder, [
        ['sku' => 'SKU-001', 'qty' => 10],
    ]);
})->throws(\InvalidArgumentException::class);

test('search available inventory returns correct results', function () {
    // Reserve some inventory first
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 30],
    ]);

    $available = $this->service->findAvailableInventory(
        $this->customer->id,
        'SKU-001'
    );

    expect($available)->toHaveCount(2);

    // Total available should be 150 - 30 = 120
    $totalAvailable = $available->sum(fn($item) => max(0, $item->qty - ($item->reserved_qty_sum ?? 0)));
    expect($totalAvailable)->toBe(120.0);
});

// ==========================================
// IDEMPOTENCY TESTS (AUD-INVRES-1)
// ==========================================

test('reserving same SKU twice does not duplicate reservations if already sufficient', function () {
    // First reservation for 30 units
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 30],
    ]);

    // Check initial state
    $initialReservations = $this->shippingOrder->inventoryReservations()->count();
    $initialReservedTotal = (float) $this->shippingOrder->inventoryReservations()->sum('qty_reserved');

    expect($initialReservations)->toBe(1);
    expect($initialReservedTotal)->toBe(30.0);

    // Attempting to reserve for the same item again should add more
    // (This is expected behavior - additional reserves are allowed, not duplicated implicit)
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 20],
    ]);

    // Should have 2 reservations now (30 + 20 = 50 total)
    $finalReservations = $this->shippingOrder->inventoryReservations()->count();
    $finalReservedTotal = (float) $this->shippingOrder->inventoryReservations()->sum('qty_reserved');

    expect($finalReservations)->toBe(2);
    expect($finalReservedTotal)->toBe(50.0);

    // But available should be reduced correctly
    $available = $this->service->findAvailableInventory($this->customer->id, 'SKU-001');
    $totalAvailable = $available->sum(fn($item) => max(0, $item->qty - ($item->reserved_qty_sum ?? 0)));
    expect($totalAvailable)->toBe(100.0); // 150 - 50 = 100
});

test('cannot reserve more than available across multiple reservations', function () {
    // Reserve 100 (from first item)
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 100],
    ]);

    // Reserve 50 more (from second item)
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 50],
    ]);

    // Total reserved should be 150
    $totalReserved = (float) $this->shippingOrder->inventoryReservations()->sum('qty_reserved');
    expect($totalReserved)->toBe(150.0);

    // Trying to reserve any more should fail
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 1],
    ]);
})->throws(\InvalidArgumentException::class);

// ==========================================
// AVAILABILITY CONSISTENCY TESTS (AUD-INVRES-1)
// ==========================================

test('availability is consistent after reserve and release cycle', function () {
    // Initial check
    $initial = $this->service->findAvailableInventory($this->customer->id, 'SKU-001');
    $initialAvailable = $initial->sum(fn($item) => max(0, $item->qty - ($item->reserved_qty_sum ?? 0)));
    expect($initialAvailable)->toBe(150.0);

    // Reserve 50
    $this->service->reserveForShippingOrder($this->shippingOrder, [
        ['sku' => 'SKU-001', 'qty' => 50],
    ]);

    $afterReserve = $this->service->findAvailableInventory($this->customer->id, 'SKU-001');
    $afterReserveAvailable = $afterReserve->sum(fn($item) => max(0, $item->qty - ($item->reserved_qty_sum ?? 0)));
    expect($afterReserveAvailable)->toBe(100.0);

    // Release all
    $this->service->releaseReservationsForShippingOrder($this->shippingOrder);

    $final = $this->service->findAvailableInventory($this->customer->id, 'SKU-001');
    $finalAvailable = $final->sum(fn($item) => max(0, $item->qty - ($item->reserved_qty_sum ?? 0)));
    expect($finalAvailable)->toBe(150.0); // Back to original
});
