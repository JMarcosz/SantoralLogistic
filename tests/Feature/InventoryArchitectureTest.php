<?php

namespace Tests\Feature;

use App\Enums\WarehouseReceiptStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptLine;
use App\Services\InventoryMovementService;
use App\Services\WarehouseReceiptStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed permissions if needed or just act as super admin
        $this->actingAs(User::factory()->create());
    }

    public function test_receiving_assigns_receiving_location_and_does_not_merge()
    {
        $warehouse = Warehouse::factory()->create();
        $customer = Customer::factory()->create();

        // Ensure Receiving location exists
        $receivingLocation = Location::factory()->create([
            'warehouse_id' => $warehouse->id,
            'code' => 'RECEIVING',
            'type' => 'dock'
        ]);

        $receipt = WarehouseReceipt::factory()->create([
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'status' => WarehouseReceiptStatus::Draft
        ]);

        // Add 2 lines of SAME item code
        $line1 = WarehouseReceiptLine::factory()->create([
            'warehouse_receipt_id' => $receipt->id,
            'item_code' => 'ITEM-A',
            'received_qty' => 10,
        ]);

        $line2 = WarehouseReceiptLine::factory()->create([
            'warehouse_receipt_id' => $receipt->id,
            'item_code' => 'ITEM-A', // Same item code
            'received_qty' => 20,
        ]);

        // Mark as Received
        $service = app(WarehouseReceiptStateMachine::class);
        $service->markReceived($receipt, auth()->id());

        // Assert 2 distinct inventory items
        $this->assertDatabaseCount('inventory_items', 2);

        $items = InventoryItem::all();

        foreach ($items as $item) {
            $this->assertEquals('ITEM-A', $item->item_code);
            $this->assertEquals($receivingLocation->id, $item->location_id); // NOT NULL
            $this->assertNotNull($item->warehouse_receipt_line_id); // Links to line
        }

        // Verify they are separate lines
        $this->assertNotEquals($items[0]->id, $items[1]->id);
        $this->assertNotEquals($items[0]->warehouse_receipt_line_id, $items[1]->warehouse_receipt_line_id);
    }

    public function test_relocate_does_not_merge_items()
    {
        $warehouse = Warehouse::factory()->create();
        $customer = Customer::factory()->create();
        $receivingLoc = Location::factory()->create(['warehouse_id' => $warehouse->id, 'code' => 'RECEIVING']);
        $rackLoc = Location::factory()->create(['warehouse_id' => $warehouse->id, 'code' => 'A-01']);

        // Create 2 items in receiving (simulating 2 different receipt lines)
        // Need to create lines first for FK constraint
        $receipt = WarehouseReceipt::factory()->create(['warehouse_id' => $warehouse->id, 'customer_id' => $customer->id]);
        $line1 = WarehouseReceiptLine::factory()->create(['warehouse_receipt_id' => $receipt->id, 'item_code' => 'ITEM-X']);
        $line2 = WarehouseReceiptLine::factory()->create(['warehouse_receipt_id' => $receipt->id, 'item_code' => 'ITEM-X']);

        $item1 = InventoryItem::factory()->create([
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'location_id' => $receivingLoc->id,
            'item_code' => 'ITEM-X',
            'qty' => 10,
            'warehouse_receipt_line_id' => $line1->id
        ]);

        $item2 = InventoryItem::factory()->create([
            'warehouse_id' => $warehouse->id,
            'customer_id' => $customer->id,
            'location_id' => $receivingLoc->id,
            'item_code' => 'ITEM-X', // Same code
            'qty' => 10,
            'warehouse_receipt_line_id' => $line2->id
        ]);

        $moveService = app(InventoryMovementService::class);

        // Move Item 1 to Rack
        $moveService->relocate($item1, $rackLoc, 10); // Full move

        // Move Item 2 to SAME Rack
        $moveService->relocate($item2, $rackLoc, 10); // Full move

        // Assert: Rack should have 2 distinct items, NOT 1 merged item
        $itemsInRack = InventoryItem::where('location_id', $rackLoc->id)->get();

        $this->assertCount(2, $itemsInRack);
        $this->assertEquals(20, $itemsInRack->sum('qty'));
        $this->assertNotEquals($itemsInRack[0]->id, $itemsInRack[1]->id);

        // Verify traceability preserved
        $this->assertEquals($line1->id, $itemsInRack->firstWhere('warehouse_receipt_line_id', $line1->id)->warehouse_receipt_line_id);
        $this->assertEquals($line2->id, $itemsInRack->firstWhere('warehouse_receipt_line_id', $line2->id)->warehouse_receipt_line_id);
    }
}
