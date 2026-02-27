<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Enums\ShippingOrderStatus;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\Quote;
use App\Models\ServiceType;
use App\Models\ShippingOrder;
use App\Models\Term;
use App\Models\TransportMode;
use App\Models\User;
use App\Services\QuoteConversionService;
use App\Services\ShippingOrderStateMachine;
use App\Services\TermsResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShippingOrderTermsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Port $originPort;
    protected Port $destinationPort;
    protected TransportMode $transportMode;
    protected ServiceType $serviceType;
    protected Currency $currency;
    protected Term $soFooterTerm;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'shipping_orders.view_any',
            'shipping_orders.view',
            'shipping_orders.create',
            'shipping_orders.update',
            'shipping_orders.change_status',
            'quotes.view',
            'quotes.convert_to_shipping_order',
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
        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$']
        );

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'TST001',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->originPort = Port::firstOrCreate(
            ['code' => 'SDQ'],
            ['name' => 'Santo Domingo', 'country' => 'DO', 'type' => 'air', 'is_active' => true]
        );

        $this->destinationPort = Port::firstOrCreate(
            ['code' => 'MIA'],
            ['name' => 'Miami', 'country' => 'US', 'type' => 'air', 'is_active' => true]
        );

        $this->transportMode = TransportMode::firstOrCreate(
            ['code' => 'AIR'],
            ['name' => 'Air Freight', 'is_active' => true]
        );

        $this->serviceType = ServiceType::firstOrCreate(
            ['code' => 'EXP'],
            ['name' => 'Express', 'is_active' => true]
        );

        // Create SO footer term
        $this->soFooterTerm = Term::create([
            'code' => 'SO_FOOTER_STD',
            'name' => 'Standard SO Terms',
            'body' => 'All shipments are subject to our standard terms and conditions.',
            'type' => Term::TYPE_SO_FOOTER,
            'is_active' => true,
        ]);

        // Create company settings with default SO terms
        CompanySetting::create([
            'name' => 'Test Company',
            'default_so_terms_id' => $this->soFooterTerm->id,
        ]);
    }

    protected function createShippingOrderData(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ], $overrides);
    }

    // ========== Manual Creation Tests ==========

    public function test_manual_creation_assigns_default_so_terms(): void
    {
        $data = $this->createShippingOrderData();

        $response = $this->actingAs($this->user)->post(route('shipping-orders.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shippingOrder = ShippingOrder::first();
        $this->assertNotNull($shippingOrder);
        $this->assertEquals($this->soFooterTerm->id, $shippingOrder->footer_terms_id);
        // Snapshot should NOT be set yet (set on booking)
        $this->assertNull($shippingOrder->footer_terms_snapshot);
    }

    public function test_manual_creation_without_company_settings_leaves_terms_null(): void
    {
        // Remove company settings
        CompanySetting::query()->delete();

        $data = $this->createShippingOrderData();

        $this->actingAs($this->user)->post(route('shipping-orders.store'), $data);

        $shippingOrder = ShippingOrder::first();
        $this->assertNotNull($shippingOrder);
        $this->assertNull($shippingOrder->footer_terms_id);
        $this->assertNull($shippingOrder->footer_terms_snapshot);
    }

    // ========== Quote Conversion Tests ==========

    public function test_conversion_from_quote_assigns_so_default_terms(): void
    {
        // Create a quote with different footer terms
        $quoteFooterTerm = Term::create([
            'code' => 'QUOTE_FOOTER',
            'name' => 'Quote Footer Terms',
            'body' => 'These are quote-specific terms.',
            'type' => Term::TYPE_QUOTE_FOOTER,
            'is_active' => true,
        ]);

        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Approved,
            'footer_terms_id' => $quoteFooterTerm->id,
            'footer_terms_snapshot' => $quoteFooterTerm->body,
        ]);

        // Convert quote to shipping order
        $conversionService = app(QuoteConversionService::class);
        $shippingOrder = $conversionService->convertToShippingOrder($quote);

        // Should use SO default terms, NOT inherited from quote (Option A)
        $this->assertEquals($this->soFooterTerm->id, $shippingOrder->footer_terms_id);
        $this->assertNotEquals($quoteFooterTerm->id, $shippingOrder->footer_terms_id);
        // Snapshot NOT captured yet (captured on booking)
        $this->assertNull($shippingOrder->footer_terms_snapshot);
    }

    // ========== Snapshot Capture Tests ==========

    public function test_booking_captures_footer_terms_snapshot(): void
    {
        // Create a shipping order with terms assigned
        $shippingOrder = ShippingOrder::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => ShippingOrderStatus::Draft,
            'footer_terms_id' => $this->soFooterTerm->id,
        ]);

        $this->assertNull($shippingOrder->footer_terms_snapshot);

        // Book the order
        $stateMachine = app(ShippingOrderStateMachine::class);
        $shippingOrder = $stateMachine->book($shippingOrder);

        // Verify snapshot was captured
        $this->assertEquals(
            'All shipments are subject to our standard terms and conditions.',
            $shippingOrder->footer_terms_snapshot
        );
    }

    public function test_snapshot_persists_after_term_modification(): void
    {
        // Create and book a shipping order
        $shippingOrder = ShippingOrder::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => ShippingOrderStatus::Draft,
            'footer_terms_id' => $this->soFooterTerm->id,
        ]);

        $stateMachine = app(ShippingOrderStateMachine::class);
        $shippingOrder = $stateMachine->book($shippingOrder);

        $originalSnapshot = $shippingOrder->footer_terms_snapshot;

        // Modify the original term
        $this->soFooterTerm->update([
            'body' => 'UPDATED: New terms that should not affect existing orders.',
        ]);

        // Refresh and verify snapshot is unchanged
        $shippingOrder->refresh();
        $this->assertEquals($originalSnapshot, $shippingOrder->footer_terms_snapshot);
        $this->assertNotEquals($this->soFooterTerm->body, $shippingOrder->footer_terms_snapshot);
    }

    // ========== Terms Resolver Service Tests ==========

    public function test_terms_resolver_returns_snapshot_first(): void
    {
        $shippingOrder = ShippingOrder::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => ShippingOrderStatus::Draft,
            'footer_terms_id' => $this->soFooterTerm->id,
            'footer_terms_snapshot' => 'This is the frozen snapshot text.',
        ]);

        $resolver = app(TermsResolverService::class);
        $text = $resolver->getShippingOrderFooterTermsText($shippingOrder);

        // Should return snapshot, not term body
        $this->assertEquals('This is the frozen snapshot text.', $text);
    }

    public function test_terms_resolver_falls_back_to_term_body(): void
    {
        $shippingOrder = ShippingOrder::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => ShippingOrderStatus::Draft,
            'footer_terms_id' => $this->soFooterTerm->id,
            // No snapshot set
        ]);

        $resolver = app(TermsResolverService::class);
        $text = $resolver->getShippingOrderFooterTermsText($shippingOrder);

        // Should return term body as fallback
        $this->assertEquals($this->soFooterTerm->body, $text);
    }

    public function test_terms_resolver_falls_back_to_company_default(): void
    {
        $shippingOrder = ShippingOrder::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => ShippingOrderStatus::Draft,
            // No footer_terms_id set
        ]);

        $resolver = app(TermsResolverService::class);
        $text = $resolver->getShippingOrderFooterTermsText($shippingOrder);

        // Should return company default terms body
        $this->assertEquals($this->soFooterTerm->body, $text);
    }

    // ========== Show Page Tests ==========

    public function test_show_page_includes_footer_terms(): void
    {
        $shippingOrder = ShippingOrder::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => ShippingOrderStatus::Draft,
            'footer_terms_id' => $this->soFooterTerm->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('shipping-orders.show', $shippingOrder));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('shipping-orders/show')
                ->has('order.footer_terms')
                ->where('order.footer_terms.id', $this->soFooterTerm->id)
                ->where('order.footer_terms.code', 'SO_FOOTER_STD')
        );
    }
}
