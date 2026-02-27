<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\PickupOrder;
use App\Models\Pod;
use App\Models\ShippingOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PickupDeliveryOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚚 Seeding Pickup and Delivery Orders with PODs...');

        // Ensure required seeders have run
        $this->ensureRequiredData();

        // Clean up previous demo data
        $this->cleanPreviousDemoData();

        // Get or create dependencies
        $customer = Customer::where('is_active', true)->first();
        if (!$customer) {
            $customer = Customer::create([
                'name' => 'Demo Customer',
                'code' => 'DEMO001',
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        $driver = Driver::where('is_active', true)->first();
        if (!$driver) {
            $driver = Driver::create([
                'name' => 'Demo Driver',
                'license_number' => 'DRV-DEMO-001',
                'phone' => '809-555-1234',
                'is_active' => true,
            ]);
        }

        $user = User::first();

        // Get required data for ShippingOrder (try active first, then any)
        $originPort = \App\Models\Port::where('is_active', true)->first() 
            ?? \App\Models\Port::first();
        $destinationPort = \App\Models\Port::where('is_active', true)->skip(1)->first()
            ?? \App\Models\Port::skip(1)->first();
        
        // If only one port exists, use it for both
        if (!$destinationPort) {
            $destinationPort = $originPort;
        }
        
        $transportMode = \App\Models\TransportMode::where('is_active', true)->first()
            ?? \App\Models\TransportMode::first();
        $serviceType = \App\Models\ServiceType::where('is_active', true)->first()
            ?? \App\Models\ServiceType::first();
        $currency = \App\Models\Currency::where('is_active', true)->first()
            ?? \App\Models\Currency::first();

        // Validate all required data is present
        if (!$originPort || !$transportMode || !$serviceType || !$currency) {
            $this->command->error('❌ Error: No se pudieron obtener los datos requeridos');
            $this->command->info('Datos encontrados:');
            $this->command->info('  • Ports: ' . \App\Models\Port::count() . ' (activos: ' . \App\Models\Port::where('is_active', true)->count() . ')');
            $this->command->info('  • Transport Modes: ' . \App\Models\TransportMode::count() . ' (activos: ' . \App\Models\TransportMode::where('is_active', true)->count() . ')');
            $this->command->info('  • Service Types: ' . \App\Models\ServiceType::count() . ' (activos: ' . \App\Models\ServiceType::where('is_active', true)->count() . ')');
            $this->command->info('  • Currencies: ' . \App\Models\Currency::count() . ' (activos: ' . \App\Models\Currency::where('is_active', true)->count() . ')');
            return;
        }

        // Create ShippingOrders
        $shippingOrders = [];
        for ($i = 1; $i <= 3; $i++) {
            $shippingOrders[] = ShippingOrder::create([
                'order_number' => 'SO-DEMO-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'origin_port_id' => $originPort->id,
                'destination_port_id' => $destinationPort->id,
                'transport_mode_id' => $transportMode->id,
                'service_type_id' => $serviceType->id,
                'currency_id' => $currency->id,
                'status' => 'in_transit',
                'total_amount' => rand(1000, 5000),
            ]);
        }

        $this->command->info('✅ Created ' . count($shippingOrders) . ' Shipping Orders');

        // Create sample PNG image (1x1 transparent pixel)
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        
        // Ensure pods directory exists
        if (!Storage::exists('pods')) {
            Storage::makeDirectory('pods');
        }

        // === PICKUP ORDERS ===
        $this->command->info('');
        $this->command->info('📦 Creating Pickup Orders...');

        // 1. Pickup Order with POD + Image + Location
        $pickup1 = PickupOrder::create([
            'reference' => 'PU-DEMO-001',
            'customer_id' => $customer->id,
            'shipping_order_id' => $shippingOrders[0]->id,
            'driver_id' => $driver->id,
            'status' => 'completed',
            'scheduled_date' => now()->subDays(2),
            'notes' => 'Av. Winston Churchill, Santo Domingo',
        ]);

        $imagePath1 = 'pods/pickup-' . $pickup1->id . '-' . now()->timestamp . '.png';
        Storage::put($imagePath1, $pngData);

        Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickup1->id,
            'happened_at' => now()->subDays(2)->setTime(14, 30),
            'latitude' => '18.4861',
            'longitude' => '-69.9312',
            'image_path' => $imagePath1,
            'notes' => 'Recogida completada exitosamente. Cliente confirmó todos los paquetes.',
            'created_by' => $user->id,
        ]);

        $this->command->info("  ✓ PU-DEMO-001 (Completed) - Con imagen y coordenadas GPS");

        // 2. Pickup Order with POD + Image (no location)
        $pickup2 = PickupOrder::create([
            'reference' => 'PU-DEMO-002',
            'customer_id' => $customer->id,
            'shipping_order_id' => $shippingOrders[1]->id,
            'driver_id' => $driver->id,
            'status' => 'completed',
            'scheduled_date' => now()->subDays(1),
            'notes' => 'Zona Colonial, Santo Domingo',
        ]);

        $imagePath2 = 'pods/pickup-' . $pickup2->id . '-' . now()->timestamp . '.png';
        Storage::put($imagePath2, $pngData);

        Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickup2->id,
            'happened_at' => now()->subDays(1)->setTime(10, 15),
            'latitude' => null,
            'longitude' => null,
            'image_path' => $imagePath2,
            'notes' => 'Firma digital capturada. Sin GPS disponible.',
            'created_by' => $user->id,
        ]);

        $this->command->info("  ✓ PU-DEMO-002 (Completed) - Con imagen, sin coordenadas");

        // 3. Pickup Order with POD (no image)
        $pickup3 = PickupOrder::create([
            'reference' => 'PU-DEMO-003',
            'customer_id' => $customer->id,
            'shipping_order_id' => $shippingOrders[2]->id,
            'driver_id' => $driver->id,
            'status' => 'completed',
            'scheduled_date' => now(),
            'notes' => 'Piantini, Santo Domingo',
        ]);

        Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickup3->id,
            'happened_at' => now()->setTime(16, 45),
            'latitude' => '18.4729',
            'longitude' => '-69.9375',
            'image_path' => null,
            'notes' => null,
            'created_by' => $user->id,
        ]);

        $this->command->info("  ✓ PU-DEMO-003 (Completed) - Sin imagen, con coordenadas");

        // 4. Pickup Order without POD (pending)
        $pickup4 = PickupOrder::create([
            'reference' => 'PU-DEMO-004',
            'customer_id' => $customer->id,
            'shipping_order_id' => $shippingOrders[0]->id,
            'driver_id' => $driver->id,
            'status' => 'pending',
            'scheduled_date' => now()->addDays(1),
            'notes' => 'La Julia, Santo Domingo'
        ]);

        $this->command->info("  ✓ PU-DEMO-004 (Pending) - Sin POD todavía");

        // === DELIVERY ORDERS ===
        $this->command->info('');
        $this->command->info('📬 Creating Delivery Orders...');

        // 1. Delivery Order with POD + Image + Location
        $delivery1 = DeliveryOrder::create([
            'reference' => 'DO-DEMO-001',
            'customer_id' => $customer->id,
            'shipping_order_id' => $shippingOrders[0]->id,
            'driver_id' => $driver->id,
            'status' => 'completed',
            'scheduled_date' => now()->subDays(1),
            'notes' => 'Bella Vista, Santo Domingo',
        ]);

        $imagePath3 = 'pods/delivery-' . $delivery1->id . '-' . now()->timestamp . '.png';
        Storage::put($imagePath3, $pngData);

        Pod::create([
            'podable_type' => DeliveryOrder::class,
            'podable_id' => $delivery1->id,
            'happened_at' => now()->subDays(1)->setTime(11, 20),
            'latitude' => '18.4682',
            'longitude' => '-69.9292',
            'image_path' => $imagePath3,
            'notes' => 'Entrega confirmada. Recibido por Juan Pérez.',
            'created_by' => $user->id,
        ]);

        $this->command->info("  ✓ DO-DEMO-001 (Completed) - Con imagen y coordenadas GPS");

        // 2. Delivery Order with POD (no image, no location)
        $delivery2 = DeliveryOrder::create([
            'reference' => 'DO-DEMO-002',
            'customer_id' => $customer->id,
            'shipping_order_id' => $shippingOrders[1]->id,
            'driver_id' => $driver->id,
            'status' => 'completed',
            'scheduled_date' => now(),
            'notes' => 'Naco, Santo Domingo'
        ]);

        Pod::create([
            'podable_type' => DeliveryOrder::class,
            'podable_id' => $delivery2->id,
            'happened_at' => now()->setTime(15, 30),
            'latitude' => null,
            'longitude' => null,
            'image_path' => null,
            'notes' => 'Entrega rápida sin firma requerida.',
            'created_by' => $user->id,
        ]);

        $this->command->info("  ✓ DO-DEMO-002 (Completed) - Sin imagen, sin coordenadas");

        // 3. Delivery Order without POD (in_progress)
        $delivery3 = DeliveryOrder::create([
            'reference' => 'DO-DEMO-003',
            'customer_id' => $customer->id,
            'shipping_order_id' => $shippingOrders[2]->id,
            'driver_id' => $driver->id,
            'status' => 'in_progress',
            'scheduled_date' => now()->addHours(2),
            'notes' => 'Los Cacicazgos, Santo Domingo'
        ]);

        $this->command->info("  ✓ DO-DEMO-003 (In Progress) - Sin POD todavía");

        // Summary
        $this->command->info('');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('✅ Seeding completado exitosamente!');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('📊 Resumen:');
        $this->command->info("  • Shipping Orders: 3");
        $this->command->info("  • Pickup Orders: 4 (3 con POD, 1 sin POD)");
        $this->command->info("  • Delivery Orders: 3 (2 con POD, 1 sin POD)");
        $this->command->info("  • PODs con imagen: 3");
        $this->command->info("  • PODs sin imagen: 2");
        $this->command->info('');
        $this->command->info('🔗 URLs para probar:');
        $this->command->info("  • /pickup-orders/{$pickup1->id} - POD completo");
        $this->command->info("  • /pickup-orders/{$pickup2->id} - POD con imagen");
        $this->command->info("  • /pickup-orders/{$pickup3->id} - POD sin imagen");
        $this->command->info("  • /pickup-orders/{$pickup4->id} - Sin POD");
        $this->command->info("  • /delivery-orders/{$delivery1->id} - POD completo");
        $this->command->info("  • /delivery-orders/{$delivery2->id} - POD mínimo");
        $this->command->info("  • /delivery-orders/{$delivery3->id} - Sin POD");
        $this->command->info('');
    }

    /**
     * Ensure required data exists by running necessary seeders.
     */
    private function ensureRequiredData(): void
    {
        // Check and seed Currencies
        if (\App\Models\Currency::count() === 0) {
            $this->command->info('💰 Seeding Currencies...');
            $this->call(CurrencySeeder::class);
        }

        // Check and seed Ports
        if (\App\Models\Port::count() === 0) {
            $this->command->info('🛳️  Seeding Ports...');
            $this->call(PortSeeder::class);
        }

        // Check and seed Transport Modes
        if (\App\Models\TransportMode::count() === 0) {
            $this->command->info('🚚 Seeding Transport Modes...');
            $this->call(TransportModeSeeder::class);
        }

        // Check and seed Service Types
        if (\App\Models\ServiceType::count() === 0) {
            $this->command->info('📦 Seeding Service Types...');
            $this->call(ServiceTypeSeeder::class);
        }

        // Check and seed Drivers if needed
        if (Driver::count() === 0) {
            $this->command->info('👤 Creating Demo Driver...');
            Driver::create([
                'name' => 'Demo Driver',
                'license_number' => 'DRV-DEMO-001',
                'phone' => '809-555-1234',
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ Todas las dependencias verificadas');
        $this->command->info('');
    }

    /**
     * Clean up previous demo data.
     */
    private function cleanPreviousDemoData(): void
    {
        $this->command->info('🧹 Limpiando datos demo anteriores...');
        
        // Delete PODs and cleanup storage for demo pickup orders
        $demoPickups = PickupOrder::where('reference', 'like', 'PU-DEMO-%')->get();
        foreach ($demoPickups as $pickup) {
            if ($pickup->pod) {
                // Delete image file if exists
                if ($pickup->pod->image_path && Storage::exists($pickup->pod->image_path)) {
                    Storage::delete($pickup->pod->image_path);
                }
                $pickup->pod->delete();
            }
        }
        PickupOrder::where('reference', 'like', 'PU-DEMO-%')->delete();
        
        // Delete PODs and cleanup storage for demo delivery orders
        $demoDeliveries = DeliveryOrder::where('reference', 'like', 'DO-DEMO-%')->get();
        foreach ($demoDeliveries as $delivery) {
            if ($delivery->pod) {
                // Delete image file if exists
                if ($delivery->pod->image_path && Storage::exists($delivery->pod->image_path)) {
                    Storage::delete($delivery->pod->image_path);
                }
                $delivery->pod->delete();
            }
        }
        DeliveryOrder::where('reference', 'like', 'DO-DEMO-%')->delete();
        
        // Delete demo shipping orders (including soft deleted ones)
        ShippingOrder::where('order_number', 'like', 'SO-DEMO-%')
            ->withTrashed()
            ->forceDelete();
        
        $this->command->info('✅ Datos anteriores eliminados');
        $this->command->info('');
    }
}
