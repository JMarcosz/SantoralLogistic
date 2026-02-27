<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ProductService;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\TransportMode;
use App\Models\User;
use App\Services\QuoteCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
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

        $this->user = User::factory()->create();

        // Create required reference data
        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$']
        );

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'TESTCUST',
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

    public function test_can_create_quote_with_auto_generated_number(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertNotNull($quote->quote_number);
        $this->assertStringStartsWith('QT-' . now()->year . '-', $quote->quote_number);
        $this->assertEquals(QuoteStatus::Draft, $quote->status);
    }

    public function test_quote_number_increments_correctly(): void
    {
        $quote1 = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $quote2 = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $seq1 = (int) substr($quote1->quote_number, -6);
        $seq2 = (int) substr($quote2->quote_number, -6);

        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_can_add_lines_and_recalculate_total(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        // Add a line: 2 x $50 = $100 + 18% tax = $118
        $quote->addLine($this->product, quantity: 2, unitPrice: 50.00);

        $quote->refresh();

        $this->assertEquals(1, $quote->lines->count());
        $this->assertEquals(100.00, (float) $quote->subtotal);
        $this->assertEquals(18.00, (float) $quote->tax_amount);
        $this->assertEquals(118.00, (float) $quote->total_amount);
    }

    public function test_line_total_calculated_on_save(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $line = QuoteLine::create([
            'quote_id' => $quote->id,
            'product_service_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => 100.00,
            'discount_percent' => 10,
            'tax_rate' => 18,
        ]);

        // Line total: 3 * 100 = 300 - 10% = 270
        $this->assertEquals(270.00, (float) $line->line_total);
    }

    public function test_relationships_load_without_n_plus_one(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $quote->addLine($this->product, quantity: 1, unitPrice: 100);

        // Load with eager loading
        $loaded = Quote::withRelations()
            ->with('lines.productService')
            ->find($quote->id);

        $this->assertTrue($loaded->relationLoaded('customer'));
        $this->assertTrue($loaded->relationLoaded('originPort'));
        $this->assertTrue($loaded->relationLoaded('destinationPort'));
        $this->assertTrue($loaded->relationLoaded('transportMode'));
        $this->assertTrue($loaded->relationLoaded('serviceType'));
        $this->assertTrue($loaded->relationLoaded('currency'));
    }

    public function test_status_transitions_work_correctly(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->assertEquals(QuoteStatus::Draft, $quote->status);

        $quote->markAsSent();
        $this->assertEquals(QuoteStatus::Sent, $quote->status);

        $quote->approve();
        $this->assertEquals(QuoteStatus::Approved, $quote->status);
    }

    public function test_invalid_status_transition_throws_exception(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->expectException(\DomainException::class);
        $quote->approve(); // Can't approve from draft
    }

    public function test_can_duplicate_quote(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        $quote->addLine($this->product, quantity: 2, unitPrice: 50);

        $this->actingAs($this->user);
        $duplicated = $quote->duplicate();

        $this->assertNotEquals($quote->id, $duplicated->id);
        $this->assertNotEquals($quote->quote_number, $duplicated->quote_number);
        $this->assertEquals(QuoteStatus::Draft, $duplicated->status);
        $this->assertEquals($quote->lines->count(), $duplicated->lines->count());
    }

    public function test_rate_fk_relationships_work(): void
    {
        $rate = Rate::create([
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'charge_basis' => 'per_shipment',
            'base_amount' => 250.00,
            'valid_from' => now()->subDay(),
        ]);

        $this->assertEquals($this->originPort->id, $rate->originPort->id);
        $this->assertEquals($this->destinationPort->id, $rate->destinationPort->id);
        $this->assertEquals($this->transportMode->id, $rate->transportMode->id);
        $this->assertEquals($this->serviceType->id, $rate->serviceType->id);
    }

    public function test_find_for_lane_returns_valid_rate(): void
    {
        Rate::create([
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
            'charge_basis' => 'per_shipment',
            'base_amount' => 250.00,
            'valid_from' => now()->subDay(),
            'is_active' => true,
        ]);

        $found = Rate::findForLane(
            $this->originPort->id,
            $this->destinationPort->id,
            $this->transportMode->id,
            $this->serviceType->id
        );

        $this->assertNotNull($found);
        $this->assertEquals(250.00, (float) $found->base_amount);
    }

    public function test_quote_calculation_service_calculates_totals(): void
    {
        $quote = Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);

        QuoteLine::create([
            'quote_id' => $quote->id,
            'product_service_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'discount_percent' => 0,
            'tax_rate' => 18,
        ]);

        $quote->load('lines');

        $service = new QuoteCalculationService();
        $totals = $service->calculateQuoteTotals($quote);

        $this->assertEquals(200.00, $totals['subtotal']);
        $this->assertEquals(36.00, $totals['tax_amount']);
        $this->assertEquals(236.00, $totals['total_amount']);
    }
}
