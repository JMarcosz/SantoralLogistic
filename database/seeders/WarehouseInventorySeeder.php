<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptLine;
use App\Enums\LocationType;
use Illuminate\Database\Seeder;

class WarehouseInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates warehouses with locations and populates inventory items for testing.
     */
    public function run(): void
    {
        $this->command->info('Starting Warehouse Inventory Seeding...');

        // 1. Create Warehouses
        $warehouses = $this->createWarehouses();

        // 2. Create Locations per Warehouse
        $this->createLocations($warehouses);

        // 3. Get active customers
        $customers = Customer::where('status', 'active')->get();
        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please run CustomerSeeder first.');
            return;
        }

        // 4. Create Warehouse Receipts and Inventory Items
        $this->createInventory($warehouses, $customers);

        $this->command->info('Warehouse Inventory seeding completed!');
    }

    private function createWarehouses(): array
    {
        $warehouseData = [
            [
                'code' => 'WH-SDQ-01',
                'name' => 'Almacén Principal Santo Domingo',
                'address' => 'Zona Industrial Hainamosa, Santo Domingo Este',
                'city' => 'Santo Domingo',
                'country' => 'República Dominicana',
                'is_active' => true,
            ],
            [
                'code' => 'WH-SDQ-02',
                'name' => 'Bodega Logística Aeropuerto',
                'address' => 'Zona Franca AILA, Las Américas',
                'city' => 'Santo Domingo',
                'country' => 'República Dominicana',
                'is_active' => true,
            ],
            [
                'code' => 'WH-STI-01',
                'name' => 'Almacén Cibao',
                'address' => 'Parque Industrial Puerto Plata Km 5',
                'city' => 'Santiago',
                'country' => 'República Dominicana',
                'is_active' => true,
            ],
        ];

        $warehouses = [];
        foreach ($warehouseData as $data) {
            $warehouse = Warehouse::firstOrCreate(
                ['code' => $data['code']],
                $data
            );
            $warehouses[] = $warehouse;
            $this->command->line("  ✓ Warehouse: {$warehouse->code} - {$warehouse->name}");
        }

        return $warehouses;
    }

    private function createLocations(array $warehouses): void
    {
        $this->command->info('Creating locations...');

        foreach ($warehouses as $warehouse) {
            // Create aisles A, B, C with racks
            foreach (['A', 'B', 'C'] as $aisle) {
                for ($rack = 1; $rack <= 5; $rack++) {
                    for ($level = 1; $level <= 4; $level++) {
                        $locationCode = "{$aisle}-{$rack}-{$level}";

                        Location::firstOrCreate(
                            [
                                'warehouse_id' => $warehouse->id,
                                'code' => $locationCode,
                            ],
                            [
                                'zone' => $aisle,
                                'type' => LocationType::Rack,
                                'max_weight_kg' => $level <= 2 ? 500 : 200,
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }

            // Create special zones
            $specialLocations = [
                ['code' => 'RECV-01', 'zone' => 'RECV', 'type' => LocationType::Staging],
                ['code' => 'RECV-02', 'zone' => 'RECV', 'type' => LocationType::Staging],
                ['code' => 'SHIP-01', 'zone' => 'SHIP', 'type' => LocationType::Dock],
                ['code' => 'SHIP-02', 'zone' => 'SHIP', 'type' => LocationType::Dock],
                ['code' => 'DAMAGED', 'zone' => 'DMG', 'type' => LocationType::Floor],
                ['code' => 'QUARANTINE', 'zone' => 'QTN', 'type' => LocationType::Floor],
            ];

            foreach ($specialLocations as $loc) {
                Location::firstOrCreate(
                    ['warehouse_id' => $warehouse->id, 'code' => $loc['code']],
                    [
                        'zone' => $loc['zone'],
                        'type' => $loc['type'],
                        'is_active' => true,
                    ]
                );
            }

            $locationCount = Location::where('warehouse_id', $warehouse->id)->count();
            $this->command->line("  ✓ {$warehouse->code}: {$locationCount} locations created");
        }
    }

    private function createInventory(array $warehouses, $customers): void
    {
        $this->command->info('Creating inventory items...');

        // Sample products by category
        $productCatalog = [
            'electronics' => [
                ['item_code' => 'ELEC-TV-55', 'description' => 'Smart TV 55" LED', 'uom' => 'unit'],
                ['item_code' => 'ELEC-LAP-15', 'description' => 'Laptop 15.6" i7 16GB', 'uom' => 'unit'],
                ['item_code' => 'ELEC-TAB-10', 'description' => 'Tablet 10.5" 128GB', 'uom' => 'unit'],
                ['item_code' => 'ELEC-AUD-BT', 'description' => 'Audífonos Bluetooth ANC', 'uom' => 'unit'],
            ],
            'apparel' => [
                ['item_code' => 'APR-TSH-M-BLK', 'description' => 'Camiseta Algodón M Negro', 'uom' => 'unit'],
                ['item_code' => 'APR-TSH-L-WHT', 'description' => 'Camiseta Algodón L Blanco', 'uom' => 'unit'],
                ['item_code' => 'APR-JNS-32-BLU', 'description' => 'Jeans Clásico 32 Azul', 'uom' => 'unit'],
                ['item_code' => 'APR-JKT-M-BLK', 'description' => 'Chaqueta Deportiva M Negro', 'uom' => 'unit'],
                ['item_code' => 'APR-SNK-42-WHT', 'description' => 'Zapatillas Running 42 Blanco', 'uom' => 'pair'],
            ],
            'food' => [
                ['item_code' => 'FOOD-RCE-5KG', 'description' => 'Arroz Premium 5kg', 'uom' => 'kg'],
                ['item_code' => 'FOOD-BNS-1KG', 'description' => 'Habichuelas Rojas 1kg', 'uom' => 'kg'],
                ['item_code' => 'FOOD-OIL-5L', 'description' => 'Aceite Vegetal 5L', 'uom' => 'liter'],
                ['item_code' => 'FOOD-SUG-2KG', 'description' => 'Azúcar Refinada 2kg', 'uom' => 'kg'],
                ['item_code' => 'FOOD-SAL-1KG', 'description' => 'Sal de Mesa 1kg', 'uom' => 'kg'],
            ],
            'industrial' => [
                ['item_code' => 'IND-MTR-5HP', 'description' => 'Motor Eléctrico 5HP', 'uom' => 'unit'],
                ['item_code' => 'IND-PMP-2IN', 'description' => 'Bomba Centrífuga 2"', 'uom' => 'unit'],
                ['item_code' => 'IND-VLV-1IN', 'description' => 'Válvula Check 1"', 'uom' => 'unit'],
                ['item_code' => 'IND-CBL-100M', 'description' => 'Cable Eléctrico 12AWG 100m', 'uom' => 'meter'],
                ['item_code' => 'IND-TB-6M', 'description' => 'Tubo Galvanizado 2" x 6m', 'uom' => 'unit'],
            ],
        ];

        $categories = array_keys($productCatalog);

        foreach ($customers as $index => $customer) {
            $category = $categories[$index % count($categories)];
            $products = $productCatalog[$category];

            // Use first 2 warehouses
            $warehouse = $warehouses[$index % 2];

            // Get storage locations
            $locations = Location::where('warehouse_id', $warehouse->id)
                ->whereNotIn('zone', ['RECV', 'SHIP', 'DMG', 'QTN'])
                ->inRandomOrder()
                ->take(count($products))
                ->get();

            // Create a receipt
            $receiptNumber = 'REC-' . date('Ymd') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            $receipt = WarehouseReceipt::firstOrCreate(
                ['receipt_number' => $receiptNumber],
                [
                    'warehouse_id' => $warehouse->id,
                    'customer_id' => $customer->id,
                    'reference' => "Importación {$customer->code}",
                    'status' => 'received',
                    'expected_at' => now()->subDays(rand(1, 30)),
                    'received_at' => now()->subDays(rand(0, 5)),
                    'notes' => "Mercancía de {$customer->name}",
                ]
            );

            // Create inventory items
            foreach ($products as $i => $product) {
                $location = $locations[$i] ?? $locations->first();
                $qty = rand(10, 100);

                // Receipt line
                WarehouseReceiptLine::firstOrCreate(
                    [
                        'warehouse_receipt_id' => $receipt->id,
                        'item_code' => $product['item_code'],
                    ],
                    [
                        'description' => $product['description'],
                        'expected_qty' => $qty,
                        'received_qty' => $qty,
                        'uom' => $product['uom'],
                    ]
                );

                // Inventory item
                InventoryItem::firstOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'customer_id' => $customer->id,
                        'item_code' => $product['item_code'],
                    ],
                    [
                        'location_id' => $location?->id,
                        'warehouse_receipt_id' => $receipt->id,
                        'description' => $product['description'],
                        'qty' => $qty,
                        'uom' => $product['uom'],
                        'lot_number' => 'LOT-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                        'received_at' => $receipt->received_at,
                    ]
                );
            }

            $this->command->line("  ✓ {$customer->name}: {$receipt->receipt_number} with " . count($products) . " products in {$warehouse->code}");
        }

        $totalItems = InventoryItem::count();
        $this->command->info("  Total inventory items: {$totalItems}");
    }
}
