<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ServiceType;
use App\Models\ShippingOrder;
use App\Models\TransportMode;
use App\Services\ShippingOrderKpiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingOrderKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingOrderKpiService $kpiService;
    protected ShippingOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kpiService = new ShippingOrderKpiService();

        // Create required dependencies
        $customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'TEST001',
            'is_active' => true,
        ]);

        $originPort = Port::create([
            'code' => 'MIA',
            'name' => 'Miami International Airport',
            'country' => 'US',
            'type' => 'air',
            'is_active' => true,
        ]);

        $destPort = Port::create([
            'code' => 'SDQ',
            'name' => 'Las Americas Airport',
            'country' => 'DO',
            'type' => 'air',
            'is_active' => true,
        ]);

        $transportMode = TransportMode::create([
            'code' => 'AIR',
            'name' => 'Air Freight',
            'is_active' => true,
        ]);

        $serviceType = ServiceType::create([
            'code' => 'EXW',
            'name' => 'Ex Works',
            'is_active' => true,
        ]);

        $currency = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_default' => true,
        ]);

        $this->order = ShippingOrder::create([
            'customer_id' => $customer->id,
            'origin_port_id' => $originPort->id,
            'destination_port_id' => $destPort->id,
            'transport_mode_id' => $transportMode->id,
            'service_type_id' => $serviceType->id,
            'currency_id' => $currency->id,
            'status' => 'draft',
        ]);
    }

    // ==================== On-Time Delivery Tests ====================

    public function test_on_time_delivery_when_actual_equals_planned(): void
    {
        $plannedDate = Carbon::now();
        $this->order->planned_arrival_at = $plannedDate;
        $this->order->actual_arrival_at = $plannedDate;

        $result = $this->kpiService->calculateOnTime($this->order);

        $this->assertTrue($result);
    }

    public function test_on_time_delivery_when_actual_before_planned(): void
    {
        $this->order->planned_arrival_at = Carbon::now()->addDays(2);
        $this->order->actual_arrival_at = Carbon::now();

        $result = $this->kpiService->calculateOnTime($this->order);

        $this->assertTrue($result);
    }

    public function test_late_delivery_when_actual_after_planned(): void
    {
        $this->order->planned_arrival_at = Carbon::now();
        $this->order->actual_arrival_at = Carbon::now()->addDays(3);

        $result = $this->kpiService->calculateOnTime($this->order);

        $this->assertFalse($result);
    }

    public function test_null_on_time_when_actual_arrival_missing(): void
    {
        $this->order->planned_arrival_at = Carbon::now();
        $this->order->actual_arrival_at = null;

        $result = $this->kpiService->calculateOnTime($this->order);

        $this->assertNull($result);
    }

    public function test_null_on_time_when_planned_arrival_missing(): void
    {
        $this->order->planned_arrival_at = null;
        $this->order->actual_arrival_at = Carbon::now();

        $result = $this->kpiService->calculateOnTime($this->order);

        $this->assertNull($result);
    }

    public function test_null_on_time_when_both_dates_missing(): void
    {
        $this->order->planned_arrival_at = null;
        $this->order->actual_arrival_at = null;

        $result = $this->kpiService->calculateOnTime($this->order);

        $this->assertNull($result);
    }

    // ==================== Delivery Delay Tests ====================

    public function test_zero_delay_when_on_time(): void
    {
        $date = Carbon::now();
        $this->order->planned_arrival_at = $date;
        $this->order->actual_arrival_at = $date;

        $result = $this->kpiService->calculateDeliveryDelay($this->order);

        $this->assertEquals(0, $result);
    }

    public function test_zero_delay_when_early(): void
    {
        $this->order->planned_arrival_at = Carbon::now()->addDays(5);
        $this->order->actual_arrival_at = Carbon::now();

        $result = $this->kpiService->calculateDeliveryDelay($this->order);

        $this->assertEquals(0, $result);
    }

    public function test_correct_delay_days_when_late(): void
    {
        $this->order->planned_arrival_at = Carbon::parse('2024-01-01');
        $this->order->actual_arrival_at = Carbon::parse('2024-01-04');

        $result = $this->kpiService->calculateDeliveryDelay($this->order);

        $this->assertEquals(3, $result);
    }

    public function test_null_delay_when_actual_missing(): void
    {
        $this->order->planned_arrival_at = Carbon::now();
        $this->order->actual_arrival_at = null;

        $result = $this->kpiService->calculateDeliveryDelay($this->order);

        $this->assertNull($result);
    }

    public function test_null_delay_when_planned_missing(): void
    {
        $this->order->planned_arrival_at = null;
        $this->order->actual_arrival_at = Carbon::now();

        $result = $this->kpiService->calculateDeliveryDelay($this->order);

        $this->assertNull($result);
    }

    // ==================== In-Full Tests (Placeholder) ====================

    public function test_in_full_returns_null_for_mvp(): void
    {
        $result = $this->kpiService->calculateInFull($this->order);

        $this->assertNull($result);
    }

    // ==================== Recalculate KPIs Tests ====================

    public function test_recalculate_kpis_sets_all_fields_for_on_time_delivery(): void
    {
        $plannedDate = Carbon::now();
        $this->order->planned_arrival_at = $plannedDate;
        $this->order->actual_arrival_at = $plannedDate;

        $this->kpiService->recalculateKpis($this->order);

        $this->assertTrue($this->order->delivered_on_time);
        $this->assertEquals(0, $this->order->delivery_delay_days);
        $this->assertNull($this->order->delivered_in_full);
    }

    public function test_recalculate_kpis_sets_all_fields_for_late_delivery(): void
    {
        $this->order->planned_arrival_at = Carbon::parse('2024-01-01');
        $this->order->actual_arrival_at = Carbon::parse('2024-01-06');

        $this->kpiService->recalculateKpis($this->order);

        $this->assertFalse($this->order->delivered_on_time);
        $this->assertEquals(5, $this->order->delivery_delay_days);
        $this->assertNull($this->order->delivered_in_full);
    }

    public function test_recalculate_kpis_handles_missing_dates(): void
    {
        $this->order->planned_arrival_at = null;
        $this->order->actual_arrival_at = null;

        $this->kpiService->recalculateKpis($this->order);

        $this->assertNull($this->order->delivered_on_time);
        $this->assertNull($this->order->delivery_delay_days);
        $this->assertNull($this->order->delivered_in_full);
    }
}
