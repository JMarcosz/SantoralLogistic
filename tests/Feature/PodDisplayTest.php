<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\PickupOrder;
use App\Models\DeliveryOrder;
use App\Models\Pod;
use App\Models\ShippingOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PodDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Driver $driver;
    protected ShippingOrder $shippingOrder;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'pickup_orders.view',
            'pickup_orders.view_any',
            'pickup_orders.register_pod',
            'delivery_orders.view',
            'delivery_orders.view_any',
            'delivery_orders.register_pod',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Create role with all permissions
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        // Create user with role
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');

        // Create reference data
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'TST001',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->driver = Driver::create([
            'name' => 'Test Driver',
            'license_number' => 'DRV123',
            'phone' => '1234567890',
            'is_active' => true,
        ]);

        // Create required dependencies for ShippingOrder
        $originPort = \App\Models\Port::firstOrCreate(
            ['code' => 'SDQ'],
            ['name' => 'Santo Domingo', 'country' => 'DO', 'type' => 'ocean', 'is_active' => true]
        );

        $destinationPort = \App\Models\Port::firstOrCreate(
            ['code' => 'MIA'],
            ['name' => 'Miami', 'country' => 'US', 'type' => 'ocean', 'is_active' => true]
        );

        $transportMode = \App\Models\TransportMode::firstOrCreate(
            ['code' => 'SEA'],
            ['name' => 'Sea Freight', 'is_active' => true]
        );

        $serviceType = \App\Models\ServiceType::firstOrCreate(
            ['code' => 'FCL'],
            ['name' => 'Full Container Load', 'is_active' => true]
        );

        $currency = \App\Models\Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_active' => true]
        );

        $this->shippingOrder = ShippingOrder::create([
            'order_number' => 'SO-TEST-001',
            'customer_id' => $this->customer->id,
            'origin_port_id' => $originPort->id,
            'destination_port_id' => $destinationPort->id,
            'transport_mode_id' => $transportMode->id,
            'service_type_id' => $serviceType->id,
            'currency_id' => $currency->id,
            'status' => 'in_transit',
        ]);
    }

    /** @test */
    public function it_displays_pod_with_image_in_pickup_order()
    {
        // Arrange: Create pickup order with POD and image
        $pickupOrder = PickupOrder::create([
            'order_number' => 'PU-001',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        Storage::fake('local');
        $imagePath = 'pods/test-image.png';
        Storage::put($imagePath, 'fake image content');

        $pod = Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickupOrder->id,
            'happened_at' => now(),
            'latitude' => '18.4861',
            'longitude' => '-69.9312',
            'image_path' => $imagePath,
            'notes' => 'Test POD with image',
            'created_by' => $this->user->id,
        ]);

        // Act: Visit pickup order detail page
        $response = $this->actingAs($this->user)->get(route('pickup-orders.show', $pickupOrder));

        // Assert: Page loads successfully
        $response->assertOk();
        
        // Assert: POD data is passed to Inertia
        $response->assertInertia(fn ($page) => 
            $page->has('order.pod')
                ->where('order.pod.id', $pod->id)
                ->where('order.pod.image_path', $imagePath)
                ->where('order.pod.latitude', '18.4861000')
                ->where('order.pod.longitude', '-69.9312000')
                ->where('order.pod.notes', 'Test POD with image')
                ->has('order.pod.created_by')
        );
    }

    /** @test */
    public function it_displays_pod_without_image_in_pickup_order()
    {
        // Arrange: Create pickup order with POD but no image
        $pickupOrder = PickupOrder::create([
            'order_number' => 'PU-002',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        $pod = Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickupOrder->id,
            'happened_at' => now(),
            'latitude' => '18.4861',
            'longitude' => '-69.9312',
            'image_path' => null,
            'notes' => 'Test POD without image',
            'created_by' => $this->user->id,
        ]);

        // Act: Visit pickup order detail page
        $response = $this->actingAs($this->user)->get(route('pickup-orders.show', $pickupOrder));

        // Assert: Page loads successfully and POD data is present
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('order.pod')
                ->where('order.pod.id', $pod->id)
                ->where('order.pod.image_path', null)
                ->where('order.pod.latitude', '18.4861000')
                ->where('order.pod.longitude', '-69.9312000')
                ->where('order.pod.notes', 'Test POD without image')
        );
    }

    /** @test */
    public function it_displays_pod_with_image_in_delivery_order()
    {
        // Arrange: Create delivery order with POD and image
        $deliveryOrder = DeliveryOrder::create([
            'order_number' => 'DO-001',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        Storage::fake('local');
        $imagePath = 'pods/delivery-test-image.png';
        Storage::put($imagePath, 'fake image content');

        $pod = Pod::create([
            'podable_type' => DeliveryOrder::class,
            'podable_id' => $deliveryOrder->id,
            'happened_at' => now(),
            'latitude' => '18.4861',
            'longitude' => '-69.9312',
            'image_path' => $imagePath,
            'notes' => 'Test delivery POD with image',
            'created_by' => $this->user->id,
        ]);

        // Act: Visit delivery order detail page
        $response = $this->actingAs($this->user)->get(route('delivery-orders.show', $deliveryOrder));

        // Assert: Page loads successfully
        $response->assertOk();
        
        // Assert: POD data is passed to Inertia
        $response->assertInertia(fn ($page) => 
            $page->has('order.pod')
                ->where('order.pod.id', $pod->id)
                ->where('order.pod.image_path', $imagePath)
                ->where('order.pod.latitude', '18.4861000')
                ->where('order.pod.longitude', '-69.9312000')
                ->where('order.pod.notes', 'Test delivery POD with image')
                ->has('order.pod.created_by')
        );
    }

    /** @test */
    public function it_displays_pod_without_image_in_delivery_order()
    {
        // Arrange: Create delivery order with POD but no image
        $deliveryOrder = DeliveryOrder::create([
            'order_number' => 'DO-002',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        $pod = Pod::create([
            'podable_type' => DeliveryOrder::class,
            'podable_id' => $deliveryOrder->id,
            'happened_at' => now(),
            'latitude' => null,
            'longitude' => null,
            'image_path' => null,
            'notes' => null,
            'created_by' => $this->user->id,
        ]);

        // Act: Visit delivery order detail page
        $response = $this->actingAs($this->user)->get(route('delivery-orders.show', $deliveryOrder));

        // Assert: Page loads successfully even with minimal POD data
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('order.pod')
                ->where('order.pod.id', $pod->id)
                ->where('order.pod.image_path', null)
                ->where('order.pod.latitude', null)
                ->where('order.pod.longitude', null)
                ->where('order.pod.notes', null)
        );
    }

    /** @test */
    public function it_serves_pod_image_correctly()
    {
        // Arrange: Create POD with image
        Storage::fake('local');
        $imagePath = 'pods/test-image.png';
        
        // Create a simple 1x1 PNG image
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        Storage::put($imagePath, $pngData);

        $pickupOrder = PickupOrder::create([
            'order_number' => 'PU-003',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        $pod = Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickupOrder->id,
            'happened_at' => now(),
            'image_path' => $imagePath,
            'created_by' => $this->user->id,
        ]);

        // Act: Request POD image
        $response = $this->actingAs($this->user)->get(route('pods.image', $pod));

        // Assert: Image is served successfully
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    /** @test */
    public function it_returns_404_when_pod_has_no_image()
    {
        // Arrange: Create POD without image
        $pickupOrder = PickupOrder::create([
            'order_number' => 'PU-004',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        $pod = Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickupOrder->id,
            'happened_at' => now(),
            'image_path' => null,
            'created_by' => $this->user->id,
        ]);

        // Act: Request POD image
        $response = $this->actingAs($this->user)->get(route('pods.image', $pod));

        // Assert: Returns 404
        $response->assertNotFound();
    }

    /** @test */
    public function it_returns_404_when_image_file_does_not_exist()
    {
        // Arrange: Create POD with non-existent image path
        Storage::fake('local');

        $pickupOrder = PickupOrder::create([
            'order_number' => 'PU-005',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        $pod = Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickupOrder->id,
            'happened_at' => now(),
            'image_path' => 'pods/non-existent-image.jpg',
            'created_by' => $this->user->id,
        ]);

        // Act: Request POD image
        $response = $this->actingAs($this->user)->get(route('pods.image', $pod));

        // Assert: Returns 404
        $response->assertNotFound();
    }

    /** @test */
    public function pickup_order_without_pod_shows_no_pod_section()
    {
        // Arrange: Create pickup order without POD
        $pickupOrder = PickupOrder::create([
            'order_number' => 'PU-006',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'pending',
        ]);

        // Act: Visit pickup order detail page
        $response = $this->actingAs($this->user)->get(route('pickup-orders.show', $pickupOrder));

        // Assert: Page loads successfully and pod is null
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->where('order.pod', null)
        );
    }

    /** @test */
    public function delivery_order_without_pod_shows_no_pod_section()
    {
        // Arrange: Create delivery order without POD
        $deliveryOrder = DeliveryOrder::create([
            'order_number' => 'DO-003',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'pending',
        ]);

        // Act: Visit delivery order detail page
        $response = $this->actingAs($this->user)->get(route('delivery-orders.show', $deliveryOrder));

        // Assert: Page loads successfully and pod is null
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->where('order.pod', null)
        );
    }

    /** @test */
    public function pod_includes_creator_information()
    {
        // Arrange: Create pickup order with POD
        $creator = User::factory()->create(['name' => 'John Doe']);

        $pickupOrder = PickupOrder::create([
            'order_number' => 'PU-007',
            'customer_id' => $this->customer->id,
            'shipping_order_id' => $this->shippingOrder->id,
            'driver_id' => $this->driver->id,
            'status' => 'completed',
        ]);

        $pod = Pod::create([
            'podable_type' => PickupOrder::class,
            'podable_id' => $pickupOrder->id,
            'happened_at' => now(),
            'created_by' => $creator->id,
        ]);

        // Act: Visit pickup order detail page
        $response = $this->actingAs($this->user)->get(route('pickup-orders.show', $pickupOrder));

        // Assert: Creator information is included
        $response->assertOk();
        $response->assertInertia(fn ($page) => 
            $page->has('order.pod.created_by')
                ->where('order.pod.created_by.id', $creator->id)
                ->where('order.pod.created_by.name', 'John Doe')
        );
    }
}
