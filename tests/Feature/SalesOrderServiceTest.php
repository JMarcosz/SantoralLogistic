<?php

use App\Enums\SalesOrderStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Port;
use App\Models\ProductService;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\ServiceType;
use App\Models\TransportMode;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'sales@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->actingAs($this->user);

    $this->currency = Currency::create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
        'is_default' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'Acme Corp',
        'code' => 'ACME01',
        'is_active' => true,
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Main Warehouse',
        'code' => 'WH-01',
        'is_active' => true,
    ]);

    // Products
    $this->product = ProductService::create([
        'code' => 'PROD-001',
        'name' => 'Widget A',
        'type' => 'product',
        'uom' => 'unit',
        'default_unit_price' => 25.00,
        'taxable' => true,
        'is_active' => true,
    ]);

    $this->service = ProductService::create([
        'code' => 'SRV-001',
        'name' => 'Assembly Service',
        'type' => 'service',
        'uom' => 'hour',
        'default_unit_price' => 50.00,
        'taxable' => true,
        'is_active' => true,
    ]);

    // Quote dependencies
    $port = Port::create(['name' => 'SDQ', 'code' => 'SDQ', 'country' => 'DO', 'type' => 'air']);
    $transportMode = TransportMode::create(['name' => 'Air', 'code' => 'AIR']);
    $serviceType = ServiceType::create(['name' => 'Standard', 'code' => 'STD']);

    // An approved quote with product + service lines
    $this->quote = Quote::create([
        'customer_id' => $this->customer->id,
        'origin_port_id' => $port->id,
        'destination_port_id' => $port->id,
        'transport_mode_id' => $transportMode->id,
        'service_type_id' => $serviceType->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
        'subtotal' => 350,
        'tax_amount' => 63,
        'total_amount' => 413,
        'created_by' => $this->user->id,
    ]);

    // Product line
    QuoteLine::create([
        'quote_id' => $this->quote->id,
        'product_service_id' => $this->product->id,
        'line_type' => 'product',
        'description' => 'Widget A',
        'quantity' => 10,
        'unit_price' => 25,
        'discount_percent' => 0,
        'tax_rate' => 18,
        'line_total' => 250,
        'sort_order' => 0,
    ]);

    // Service line
    QuoteLine::create([
        'quote_id' => $this->quote->id,
        'product_service_id' => $this->service->id,
        'line_type' => 'service',
        'description' => 'Assembly',
        'quantity' => 2,
        'unit_price' => 50,
        'discount_percent' => 0,
        'tax_rate' => 18,
        'line_total' => 100,
        'sort_order' => 1,
    ]);

    // Inventory for reservations (20 units of product)
    $this->inventoryItem = InventoryItem::create([
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'product_service_id' => $this->product->id,
        'item_code' => 'PROD-001',
        'description' => 'Widget A',
        'qty' => 20,
        'uom' => 'unit',
        'received_at' => now()->subDays(3),
    ]);

    $this->salesOrderService = app(SalesOrderService::class);
});

// ========== STATUS ENUM TESTS ==========

test('SalesOrderStatus has valid transitions from draft', function () {
    $status = SalesOrderStatus::Draft;
    expect($status->canTransitionTo(SalesOrderStatus::Confirmed))->toBeTrue();
    expect($status->canTransitionTo(SalesOrderStatus::Cancelled))->toBeTrue();
    expect($status->canTransitionTo(SalesOrderStatus::Delivering))->toBeFalse();
    expect($status->canTransitionTo(SalesOrderStatus::Delivered))->toBeFalse();
    expect($status->canTransitionTo(SalesOrderStatus::Invoiced))->toBeFalse();
});

test('SalesOrderStatus has valid transitions from confirmed', function () {
    $status = SalesOrderStatus::Confirmed;
    expect($status->canTransitionTo(SalesOrderStatus::Delivering))->toBeTrue();
    expect($status->canTransitionTo(SalesOrderStatus::Cancelled))->toBeTrue();
    expect($status->canTransitionTo(SalesOrderStatus::Draft))->toBeFalse();
});

test('SalesOrderStatus terminal states have no transitions', function () {
    expect(SalesOrderStatus::Invoiced->isTerminal())->toBeTrue();
    expect(SalesOrderStatus::Cancelled->isTerminal())->toBeTrue();
    expect(SalesOrderStatus::Invoiced->validTransitions())->toHaveCount(0);
    expect(SalesOrderStatus::Cancelled->validTransitions())->toHaveCount(0);
});

// ========== CREATE FROM QUOTE TESTS ==========

test('createFromQuote creates order with correct data', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);

    expect($order)->toBeInstanceOf(SalesOrder::class);
    expect($order->quote_id)->toBe($this->quote->id);
    expect($order->customer_id)->toBe($this->customer->id);
    expect($order->currency_id)->toBe($this->currency->id);
    expect($order->status)->toBe(SalesOrderStatus::Draft);
    expect($order->order_number)->toStartWith('PED-' . date('Y') . '-');
});

test('createFromQuote copies all quote lines preserving line_type', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);
    $lines = $order->lines()->orderBy('sort_order')->get();

    expect($lines)->toHaveCount(2);

    // Product line
    expect($lines[0]->line_type)->toBe('product');
    expect($lines[0]->product_service_id)->toBe($this->product->id);
    expect((float) $lines[0]->quantity)->toBe(10.0);
    expect((float) $lines[0]->unit_price)->toBe(25.0);

    // Service line
    expect($lines[1]->line_type)->toBe('service');
    expect($lines[1]->product_service_id)->toBe($this->service->id);
});

test('createFromQuote blocks duplicate conversion', function () {
    $this->salesOrderService->createFromQuote($this->quote);
    $this->salesOrderService->createFromQuote($this->quote);
})->throws(\RuntimeException::class, 'ya tiene una orden');

// ========== CONFIRM TESTS ==========

test('confirm transitions to confirmed and reserves inventory', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);
    $result = $this->salesOrderService->confirm($order);

    $order->refresh();
    expect($order->status)->toBe(SalesOrderStatus::Confirmed);
    expect($order->confirmed_at)->not->toBeNull();

    // Should have reservations for the product line (10 units)
    $reservations = $order->inventoryReservations;
    expect($reservations)->toHaveCount(1);
    expect((float) $reservations->first()->qty_reserved)->toBe(10.0);
    expect($reservations->first()->sales_order_id)->toBe($order->id);
});

test('confirm with insufficient stock returns warnings', function () {
    // Reduce inventory to 5 (less than the 10 required)
    $this->inventoryItem->update(['qty' => 5]);

    $order = $this->salesOrderService->createFromQuote($this->quote);
    $result = $this->salesOrderService->confirm($order);

    // Order still confirmed
    $order->refresh();
    expect($order->status)->toBe(SalesOrderStatus::Confirmed);

    // Should have warnings about insufficient stock
    expect($result['warnings'])->not->toBeEmpty();

    // Should have partial reservation for 5
    $totalReserved = (float) $order->inventoryReservations()->sum('qty_reserved');
    expect($totalReserved)->toBe(5.0);
});

test('confirm only from draft', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);
    $result = $this->salesOrderService->confirm($order);

    // Try confirming again
    $order->refresh();
    $this->salesOrderService->confirm($order);
})->throws(\RuntimeException::class, 'borrador');

// ========== DELIVERY TESTS ==========

test('startDelivery transitions from confirmed', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);
    $this->salesOrderService->confirm($order);
    $order->refresh();

    $updated = $this->salesOrderService->startDelivery($order);

    expect($updated->status)->toBe(SalesOrderStatus::Delivering);
});

test('startDelivery only from confirmed', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);
    $this->salesOrderService->startDelivery($order);
})->throws(\RuntimeException::class);

test('markDelivered deducts inventory and transitions', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);
    $this->salesOrderService->confirm($order);
    $order->refresh();

    $this->salesOrderService->startDelivery($order);
    $order->refresh();

    $this->salesOrderService->markDelivered($order);
    $order->refresh();

    expect($order->status)->toBe(SalesOrderStatus::Delivered);
    expect($order->delivered_at)->not->toBeNull();

    // Inventory should be deducted (20 - 10 = 10)
    $this->inventoryItem->refresh();
    expect((float) $this->inventoryItem->qty)->toBe(10.0);

    // Movement should be recorded
    $movements = InventoryMovement::where('reference', $order->order_number)->get();
    expect($movements)->not->toBeEmpty();
});

// ========== MODEL TESTS ==========

test('SalesOrder auto-generates order number', function () {
    $order = SalesOrder::create([
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'created_by' => $this->user->id,
    ]);

    expect($order->order_number)->toStartWith('PED-' . date('Y') . '-');
    expect(strlen($order->order_number))->toBe(15); // PED-YYYY-NNNNNN
});

test('SalesOrder recalculates totals from lines', function () {
    $order = SalesOrder::create([
        'customer_id' => $this->customer->id,
        'currency_id' => $this->currency->id,
        'created_by' => $this->user->id,
    ]);

    SalesOrderLine::create([
        'sales_order_id' => $order->id,
        'product_service_id' => $this->product->id,
        'line_type' => 'product',
        'description' => 'Widget A',
        'quantity' => 5,
        'unit_price' => 20,
        'discount_percent' => 10,
        'tax_rate' => 18,
        'line_total' => 90,
    ]);

    $order->recalculateTotals();
    $order->refresh();

    // 5 * 20 = 100, 10% disc = 90, 18% tax = 16.2
    expect((float) $order->subtotal)->toBe(90.0);
    expect(round((float) $order->tax_amount, 2))->toBe(16.20);
    expect(round((float) $order->total_amount, 2))->toBe(106.20);
});

test('SalesOrderLine isProduct and isService helpers work', function () {
    $productLine = new SalesOrderLine(['line_type' => 'product']);
    $serviceLine = new SalesOrderLine(['line_type' => 'service']);

    expect($productLine->isProduct())->toBeTrue();
    expect($productLine->isService())->toBeFalse();
    expect($serviceLine->isService())->toBeTrue();
    expect($serviceLine->isProduct())->toBeFalse();
});

// ========== PRODUCT SERVICE TESTS ==========

test('ProductService isProduct, isService, isFee work', function () {
    expect($this->product->isProduct())->toBeTrue();
    expect($this->product->isService())->toBeFalse();
    expect($this->service->isService())->toBeTrue();
    expect($this->service->isProduct())->toBeFalse();
});

test('ProductService has inventoryItems relationship', function () {
    $items = $this->product->inventoryItems;
    expect($items)->toHaveCount(1);
    expect($items->first()->item_code)->toBe('PROD-001');
});

// ========== QUOTE LINE TYPE TEST ==========

test('QuoteLine preserves line_type', function () {
    $productLine = QuoteLine::where('line_type', 'product')->first();
    $serviceLine = QuoteLine::where('line_type', 'service')->first();

    expect($productLine)->not->toBeNull();
    expect($serviceLine)->not->toBeNull();
    expect($productLine->product_service_id)->toBe($this->product->id);
    expect($serviceLine->product_service_id)->toBe($this->service->id);
});

// ========== INVENTORY RESERVATION FOR SALES ORDER ==========

test('InventoryReservation links to sales order', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);
    $this->salesOrderService->confirm($order);
    $order->refresh();

    $reservation = $order->inventoryReservations->first();
    expect($reservation)->not->toBeNull();
    expect($reservation->salesOrder->id)->toBe($order->id);
    expect($reservation->shipping_order_id)->toBeNull();
});

// ========== CANCEL TESTS ==========

test('cancel from draft succeeds', function () {
    $order = $this->salesOrderService->createFromQuote($this->quote);

    $order->status = SalesOrderStatus::Cancelled;
    $order->save();

    expect($order->status)->toBe(SalesOrderStatus::Cancelled);
});

test('cannot cancel delivered order', function () {
    expect(SalesOrderStatus::Delivered->canTransitionTo(SalesOrderStatus::Cancelled))->toBeFalse();
});

// ========== FULL FLOW TEST ==========

test('complete flow: quote → sales order → confirm → deliver → delivered', function () {
    // 1. Create from quote
    $order = $this->salesOrderService->createFromQuote($this->quote);
    expect($order->status)->toBe(SalesOrderStatus::Draft);
    expect($order->lines)->toHaveCount(2);

    // 2. Confirm (auto-reserves)
    $result = $this->salesOrderService->confirm($order);
    $order->refresh();
    expect($order->status)->toBe(SalesOrderStatus::Confirmed);
    expect($order->inventoryReservations)->toHaveCount(1);

    // 3. Start delivery
    $this->salesOrderService->startDelivery($order);
    $order->refresh();
    expect($order->status)->toBe(SalesOrderStatus::Delivering);

    // 4. Mark delivered (deducts inventory)
    $this->salesOrderService->markDelivered($order);
    $order->refresh();
    expect($order->status)->toBe(SalesOrderStatus::Delivered);

    // Inventory should be deducted
    $this->inventoryItem->refresh();
    expect((float) $this->inventoryItem->qty)->toBe(10.0); // 20 - 10 = 10

    // Verify movements were recorded
    $movements = InventoryMovement::where('reference', $order->order_number)->count();
    expect($movements)->toBeGreaterThanOrEqual(2); // 1 reserve + 1 pick
});
