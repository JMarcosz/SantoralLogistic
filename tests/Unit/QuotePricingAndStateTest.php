<?php

namespace Tests\Unit;

use App\Enums\QuoteStatus;
use App\Exceptions\InvalidQuoteStateTransitionException;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ProductService;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\ServiceType;
use App\Models\TransportMode;
use App\Services\QuotePricingService;
use App\Services\QuoteStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Quote pricing service and state machine.
 */
class QuotePricingAndStateTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;
    protected Customer $customer;
    protected Port $originPort;
    protected Port $destinationPort;
    protected TransportMode $transportMode;
    protected ServiceType $serviceType;
    protected ProductService $product;
    protected QuotePricingService $pricingService;
    protected QuoteStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricingService = new QuotePricingService();
        $this->stateMachine = new QuoteStateMachine();

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

    protected function createQuote(): Quote
    {
        return Quote::create([
            'customer_id' => $this->customer->id,
            'origin_port_id' => $this->originPort->id,
            'destination_port_id' => $this->destinationPort->id,
            'transport_mode_id' => $this->transportMode->id,
            'service_type_id' => $this->serviceType->id,
            'currency_id' => $this->currency->id,
        ]);
    }

    // ========== PRICING SERVICE TESTS ==========

    public function test_pricing_calculates_line_correctly(): void
    {
        $line = new QuoteLine([
            'quantity' => 2,
            'unit_price' => 100.00,
            'discount_percent' => 10,
            'tax_rate' => 18,
        ]);

        $result = $this->pricingService->calculateLine($line);

        // 2 * 100 = 200 gross
        // 200 * 10% = 20 discount
        // 200 - 20 = 180 net
        // 180 * 18% = 32.40 tax
        $this->assertEquals(200.00, $result['gross_amount']);
        $this->assertEquals(20.00, $result['discount_amount']);
        $this->assertEquals(180.00, $result['net_amount']);
        $this->assertEquals(32.40, $result['tax_amount']);
        $this->assertEquals(212.40, $result['total']);
    }

    public function test_pricing_calculates_quote_totals_with_multiple_lines(): void
    {
        $quote = $this->createQuote();

        // Line 1: 2 x 100 = 200, no discount, 18% tax
        $quote->lines()->create([
            'product_service_id' => $this->product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'discount_percent' => 0,
            'tax_rate' => 18,
            'line_total' => 200,
        ]);

        // Line 2: 5 x 50 = 250, 10% discount = 225, 0% tax
        $quote->lines()->create([
            'product_service_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 50.00,
            'discount_percent' => 10,
            'tax_rate' => 0,
            'line_total' => 225,
        ]);

        $quote->load('lines');
        $totals = $this->pricingService->calculateTotals($quote);

        // Line 1 net: 200, tax: 36
        // Line 2 net: 225, tax: 0
        // Total subtotal: 425, tax: 36, total: 461
        $this->assertEquals(425.00, $totals['subtotal']);
        $this->assertEquals(36.00, $totals['tax_amount']);
        $this->assertEquals(461.00, $totals['total_amount']);
        $this->assertEquals(2, $totals['line_count']);
    }

    public function test_pricing_persists_totals_transactionally(): void
    {
        $quote = $this->createQuote();

        $quote->lines()->create([
            'product_service_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => 100.00,
            'discount_percent' => 0,
            'tax_rate' => 18,
            'line_total' => 300,
        ]);

        $updated = $this->pricingService->recalculateAndPersist($quote);

        $this->assertEquals(300.00, (float) $updated->subtotal);
        $this->assertEquals(54.00, (float) $updated->tax_amount);
        $this->assertEquals(354.00, (float) $updated->total_amount);

        // Verify persisted
        $reloaded = Quote::find($quote->id);
        $this->assertEquals(354.00, (float) $reloaded->total_amount);
    }

    public function test_pricing_adds_line_and_recalculates(): void
    {
        $quote = $this->createQuote();

        $line = $this->pricingService->addLineAndRecalculate(
            $quote,
            $this->product->id,
            quantity: 4,
            unitPrice: 25.00,
            taxRate: 18,
        );

        $this->assertNotNull($line->id);
        $this->assertEquals(100.00, (float) $line->line_total);

        $quote->refresh();
        $this->assertEquals(100.00, (float) $quote->subtotal);
        $this->assertEquals(18.00, (float) $quote->tax_amount);
        $this->assertEquals(118.00, (float) $quote->total_amount);
    }

    public function test_pricing_removes_line_and_recalculates(): void
    {
        $quote = $this->createQuote();

        // Add two lines
        $line1 = $quote->lines()->create([
            'product_service_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'discount_percent' => 0,
            'tax_rate' => 0,
            'line_total' => 100,
        ]);

        $quote->lines()->create([
            'product_service_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'discount_percent' => 0,
            'tax_rate' => 0,
            'line_total' => 50,
        ]);

        $this->pricingService->recalculateAndPersist($quote);
        $quote->refresh();
        $this->assertEquals(150.00, (float) $quote->total_amount);

        // Remove first line
        $this->pricingService->removeLineAndRecalculate($line1);

        $quote->refresh();
        $this->assertEquals(50.00, (float) $quote->total_amount);
        $this->assertEquals(1, $quote->lines()->count());
    }

    // ========== STATE MACHINE TESTS ==========

    public function test_state_machine_allows_valid_draft_to_sent(): void
    {
        $quote = $this->createQuote();

        $this->assertTrue($this->stateMachine->canTransition(QuoteStatus::Draft, QuoteStatus::Sent));

        $updated = $this->stateMachine->send($quote);

        $this->assertEquals(QuoteStatus::Sent, $updated->status);
    }

    public function test_state_machine_allows_valid_sent_to_approved(): void
    {
        $quote = $this->createQuote();
        $quote->update(['status' => QuoteStatus::Sent]);

        $this->assertTrue($this->stateMachine->canTransition(QuoteStatus::Sent, QuoteStatus::Approved));

        $updated = $this->stateMachine->approve($quote);

        $this->assertEquals(QuoteStatus::Approved, $updated->status);
    }

    public function test_state_machine_allows_valid_sent_to_rejected(): void
    {
        $quote = $this->createQuote();
        $quote->update(['status' => QuoteStatus::Sent]);

        $this->assertTrue($this->stateMachine->canTransition(QuoteStatus::Sent, QuoteStatus::Rejected));

        $updated = $this->stateMachine->reject($quote);

        $this->assertEquals(QuoteStatus::Rejected, $updated->status);
    }

    public function test_state_machine_rejects_draft_to_approved(): void
    {
        $quote = $this->createQuote();

        $this->assertFalse($this->stateMachine->canTransition(QuoteStatus::Draft, QuoteStatus::Approved));

        $this->expectException(InvalidQuoteStateTransitionException::class);
        $this->stateMachine->approve($quote);
    }

    public function test_state_machine_rejects_draft_to_rejected(): void
    {
        $quote = $this->createQuote();

        $this->assertFalse($this->stateMachine->canTransition(QuoteStatus::Draft, QuoteStatus::Rejected));

        $this->expectException(InvalidQuoteStateTransitionException::class);
        $this->stateMachine->reject($quote);
    }

    public function test_state_machine_rejects_approved_to_any(): void
    {
        $quote = $this->createQuote();
        $quote->update(['status' => QuoteStatus::Approved]);

        $this->assertTrue($this->stateMachine->isFinalized($quote));
        $this->assertFalse($this->stateMachine->canEdit($quote));

        $this->expectException(InvalidQuoteStateTransitionException::class);
        $this->stateMachine->send($quote);
    }

    public function test_state_machine_rejects_rejected_to_any(): void
    {
        $quote = $this->createQuote();
        $quote->update(['status' => QuoteStatus::Rejected]);

        $this->assertTrue($this->stateMachine->isFinalized($quote));

        $this->expectException(InvalidQuoteStateTransitionException::class);
        $this->stateMachine->approve($quote);
    }

    public function test_state_machine_returns_valid_transitions(): void
    {
        $draftTransitions = $this->stateMachine->getValidTransitions(QuoteStatus::Draft);
        $this->assertCount(1, $draftTransitions);
        $this->assertEquals(QuoteStatus::Sent, $draftTransitions[0]);

        $sentTransitions = $this->stateMachine->getValidTransitions(QuoteStatus::Sent);
        $this->assertCount(2, $sentTransitions);
        $this->assertContains(QuoteStatus::Approved, $sentTransitions);
        $this->assertContains(QuoteStatus::Rejected, $sentTransitions);

        $approvedTransitions = $this->stateMachine->getValidTransitions(QuoteStatus::Approved);
        $this->assertCount(0, $approvedTransitions);
    }

    public function test_can_edit_only_in_draft(): void
    {
        $quote = $this->createQuote();

        $this->assertTrue($this->stateMachine->canEdit($quote));

        $quote->update(['status' => QuoteStatus::Sent]);
        $this->assertFalse($this->stateMachine->canEdit($quote));

        $quote->update(['status' => QuoteStatus::Approved]);
        $this->assertFalse($this->stateMachine->canEdit($quote));
    }

    // ========== EXCEPTION TESTS ==========

    public function test_exception_contains_from_and_to_status(): void
    {
        $exception = new InvalidQuoteStateTransitionException('draft', 'approved', 'Test reason');

        $this->assertEquals('draft', $exception->fromStatus);
        $this->assertEquals('approved', $exception->toStatus);
        $this->assertStringContainsString('draft', $exception->getMessage());
        $this->assertStringContainsString('approved', $exception->getMessage());
    }

    public function test_exception_factory_methods(): void
    {
        $sendEx = InvalidQuoteStateTransitionException::cannotSend('approved');
        $this->assertEquals('approved', $sendEx->fromStatus);
        $this->assertEquals('sent', $sendEx->toStatus);

        $approveEx = InvalidQuoteStateTransitionException::cannotApprove('draft');
        $this->assertEquals('draft', $approveEx->fromStatus);
        $this->assertEquals('approved', $approveEx->toStatus);

        $rejectEx = InvalidQuoteStateTransitionException::cannotReject('draft');
        $this->assertEquals('draft', $rejectEx->fromStatus);
        $this->assertEquals('rejected', $rejectEx->toStatus);
    }
}
