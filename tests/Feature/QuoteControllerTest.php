<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ProductService;
use App\Models\Quote;
use App\Models\ServiceType;
use App\Models\ShippingOrder;
use App\Models\Term;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuoteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Port $originPort;
    protected Port $destinationPort;
    protected TransportMode $transportMode;
    protected ServiceType $serviceType;
    protected Currency $currency;
    protected ProductService $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'quotes.view_any',
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.delete',
            'quotes.send',
            'quotes.approve',
            'quotes.reject',
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

        $this->product = ProductService::firstOrCreate(
            ['code' => 'HANDLING'],
            [
                'name' => 'Handling Fee',
                'type' => 'fee',
                'default_unit_price' => 50.00,
                'taxable' => true,
                'is_active' => true,
            ]
        );
    }

    protected function createQuoteData(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'lines' => [
                [
                    'product_service_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                    'tax_rate' => 18,
                ],
            ],
        ], $overrides);
    }

    // ========== CRUD Tests ==========

    public function test_index_displays_quotes(): void
    {
        Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('quotes.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('quotes/index')
                ->has('quotes.data', 1)
        );
    }

    public function test_index_filters_by_status(): void
    {
        Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
        ]);

        Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Sent,
        ]);

        $response = $this->actingAs($this->user)->get(route('quotes.index', ['status' => 'draft']));

        $response->assertInertia(
            fn($page) => $page
                ->has('quotes.data', 1)
                ->where('quotes.data.0.status', 'draft')
        );
    }

    public function test_store_creates_quote_with_lines(): void
    {
        $data = $this->createQuoteData();

        $response = $this->actingAs($this->user)->post(route('quotes.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $quote = Quote::first();
        $this->assertNotNull($quote);
        $this->assertNotNull($quote->quote_number);
        $this->assertEquals(1, $quote->lines()->count());

        // Verify total is calculated: 2 * 100 = 200 + 18% tax = 236
        $this->assertEquals(200.00, (float) $quote->subtotal);
        $this->assertEquals(36.00, (float) $quote->tax_amount);
        $this->assertEquals(236.00, (float) $quote->total_amount);
    }

    public function test_store_requires_at_least_one_line(): void
    {
        $data = $this->createQuoteData(['lines' => []]);

        $response = $this->actingAs($this->user)->post(route('quotes.store'), $data);

        $response->assertSessionHasErrors('lines');
    }

    public function test_show_displays_quote_details(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('quotes.show', $quote));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('quotes/show')
                ->has('quote')
                ->has('can')
        );
    }

    public function test_update_modifies_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
        ]);

        $data = $this->createQuoteData([
            'lines' => [
                [
                    'product_service_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_price' => 50.00,
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $response = $this->actingAs($this->user)->put(route('quotes.update', $quote), $data);

        $response->assertRedirect();

        $quote->refresh();
        // 5 * 50 = 250, no tax
        $this->assertEquals(250.00, (float) $quote->total_amount);
    }

    public function test_cannot_update_non_draft_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Sent,
        ]);

        $data = $this->createQuoteData();

        $response = $this->actingAs($this->user)->put(route('quotes.update', $quote), $data);

        $response->assertForbidden();
    }

    public function test_destroy_soft_deletes_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->delete(route('quotes.destroy', $quote));

        $response->assertRedirect(route('quotes.index'));
        $this->assertSoftDeleted($quote);
    }

    // ========== State Transition Tests ==========

    public function test_send_transitions_draft_to_sent(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->post(route('quotes.send', $quote));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $quote->refresh();
        $this->assertEquals(QuoteStatus::Sent, $quote->status);
    }

    public function test_approve_transitions_sent_to_approved(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Sent,
        ]);

        $response = $this->actingAs($this->user)->post(route('quotes.approve', $quote));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $quote->refresh();
        $this->assertEquals(QuoteStatus::Approved, $quote->status);
    }

    public function test_reject_transitions_sent_to_rejected(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Sent,
        ]);

        $response = $this->actingAs($this->user)->post(route('quotes.reject', $quote));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $quote->refresh();
        $this->assertEquals(QuoteStatus::Rejected, $quote->status);
    }

    public function test_cannot_approve_draft_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->post(route('quotes.approve', $quote));

        $response->assertForbidden();
    }

    // ========== Conversion Tests ==========

    public function test_convert_creates_shipping_order(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Approved,
            'total_amount' => 500.00,
        ]);

        $response = $this->actingAs($this->user)->post(route('quotes.convert', $quote));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $shippingOrder = ShippingOrder::where('quote_id', $quote->id)->first();
        $this->assertNotNull($shippingOrder);
        $this->assertEquals($quote->customer_id, $shippingOrder->customer_id);
        $this->assertEquals(500.00, (float) $shippingOrder->total_amount);
    }

    public function test_cannot_convert_non_approved_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
        ]);

        $response = $this->actingAs($this->user)->post(route('quotes.convert', $quote));

        $response->assertForbidden();
        $this->assertEquals(0, ShippingOrder::count());
    }

    public function test_cannot_convert_already_converted_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Approved,
        ]);

        // First conversion
        $this->actingAs($this->user)->post(route('quotes.convert', $quote));

        // Second attempt
        $response = $this->actingAs($this->user)->post(route('quotes.convert', $quote));

        // Policy prevents conversion (since canConvert returns false)
        $response->assertForbidden();
        $this->assertEquals(1, ShippingOrder::count());
    }

    // ========== Terms Integration Tests ==========

    public function test_store_assigns_terms_ids(): void
    {
        // Create payment and footer terms
        $paymentTerm = Term::create([
            'code' => 'NET30',
            'name' => 'Net 30 Days',
            'body' => 'Payment due within 30 days.',
            'type' => Term::TYPE_PAYMENT,
            'is_active' => true,
        ]);

        $footerTerm = Term::create([
            'code' => 'STD_QUOTE',
            'name' => 'Standard Quote Terms',
            'body' => 'Prices valid for 30 days.',
            'type' => Term::TYPE_QUOTE_FOOTER,
            'is_active' => true,
        ]);

        $data = $this->createQuoteData([
            'payment_terms_id' => $paymentTerm->id,
            'footer_terms_id' => $footerTerm->id,
        ]);

        $this->actingAs($this->user)->post(route('quotes.store'), $data);

        $quote = Quote::first();
        $this->assertEquals($paymentTerm->id, $quote->payment_terms_id);
        $this->assertEquals($footerTerm->id, $quote->footer_terms_id);
    }

    public function test_send_captures_terms_snapshots(): void
    {
        // Create terms
        $paymentTerm = Term::create([
            'code' => 'NET30',
            'name' => 'Net 30 Days',
            'body' => 'Payment due within 30 days.',
            'type' => Term::TYPE_PAYMENT,
            'is_active' => true,
        ]);

        $footerTerm = Term::create([
            'code' => 'STD_QUOTE',
            'name' => 'Standard Quote Terms',
            'body' => 'Prices valid for 30 days.',
            'type' => Term::TYPE_QUOTE_FOOTER,
            'is_active' => true,
        ]);

        // Create a quote with terms
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'status' => QuoteStatus::Draft,
            'payment_terms_id' => $paymentTerm->id,
            'footer_terms_id' => $footerTerm->id,
        ]);

        // Send the quote
        $this->actingAs($this->user)->post(route('quotes.send', $quote));

        $quote->refresh();

        // Verify snapshots were captured
        $this->assertEquals('Payment due within 30 days.', $quote->payment_terms_snapshot);
        $this->assertEquals('Prices valid for 30 days.', $quote->footer_terms_snapshot);

        // Modify the original term
        $paymentTerm->update(['body' => 'Payment due within 60 days.']);

        // The snapshot should still contain the original text
        $quote->refresh();
        $this->assertEquals('Payment due within 30 days.', $quote->payment_terms_snapshot);
    }
}
