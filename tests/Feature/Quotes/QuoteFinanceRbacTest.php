<?php

namespace Tests\Feature\Quotes;

use App\Models\Quote;
use App\Models\User;
use App\Models\ProductService;
use App\Models\Customer;
use App\Models\Port;
use App\Models\TransportMode;
use App\Models\ServiceType;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class QuoteFinanceRbacTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $salesRep;
    protected $product;
    protected $commonData;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup permissions
        $permissions = [
            'quotes.view_any',
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Setup Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $salesRole = Role::firstOrCreate(['name' => 'sales']);

        $salesRole->givePermissionTo($permissions);
        $adminRole->givePermissionTo($permissions);

        // Setup Users
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->salesRep = User::factory()->create();
        $this->salesRep->assignRole($salesRole);

        // Setup Product
        $this->product = ProductService::create([
            'code' => 'TEST-SVC',
            'name' => 'Test Service',
            'type' => 'service',
            'default_unit_price' => 100,
            'is_active' => true,
        ]);

        $this->createCommonData();
    }

    protected function createCommonData()
    {
        $this->commonData = [
            'customer_id' => Customer::create(['name' => 'Test Customer', 'code' => 'CUST01', 'is_active' => true])->id,
            'origin_port_id' => Port::create(['name' => 'Port A', 'code' => 'PTA', 'country' => 'US', 'type' => 'ocean', 'is_active' => true])->id,
            'destination_port_id' => Port::create(['name' => 'Port B', 'code' => 'PTB', 'country' => 'DO', 'type' => 'ocean', 'is_active' => true])->id,
            'transport_mode_id' => TransportMode::create(['name' => 'Sea', 'code' => 'SEA', 'is_active' => true])->id,
            'service_type_id' => ServiceType::create(['name' => 'FCL', 'code' => 'FCL', 'is_active' => true])->id,
            'currency_id' => Currency::create(['name' => 'USD', 'code' => 'USD', 'symbol' => '$'])->id,
        ];
    }

    protected function createQuote(User $user, array $overrides = [])
    {
        return Quote::create(array_merge([
            'quote_number' => 'Q-' . uniqid(),
            'created_by' => $user->id,
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
        ], $this->commonData, $overrides));
    }

    public function test_admin_can_set_unit_cost()
    {
        $payload = array_merge($this->commonData, [
            'lines' => [
                [
                    'product_service_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 100,
                    'unit_cost' => 50, // Cost provided
                ]
            ]
        ]);

        $response = $this->actingAs($this->admin)->post(route('quotes.store'), $payload);

        $response->assertRedirect();

        $quote = Quote::latest()->first();
        $this->assertEquals(50, $quote->lines->first()->unit_cost);
    }

    public function test_sales_cannot_set_unit_cost()
    {
        $payload = array_merge($this->commonData, [
            'lines' => [
                [
                    'product_service_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 100,
                    'unit_cost' => 50, // Cost provided by sales
                ]
            ]
        ]);

        $response = $this->actingAs($this->salesRep)->post(route('quotes.store'), $payload);

        // Should validation error on lines.0.unit_cost
        $response->assertSessionHasErrors('lines.0.unit_cost');
    }

    public function test_sales_can_create_without_unit_cost_and_defaults_to_zero()
    {
        $payload = array_merge($this->commonData, [
            'lines' => [
                [
                    'product_service_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 100,
                    // No unit_cost
                ]
            ]
        ]);

        $response = $this->actingAs($this->salesRep)->post(route('quotes.store'), $payload);

        $response->assertRedirect();

        $quote = Quote::where('created_by', $this->salesRep->id)->latest()->first();
        $this->assertNull($quote->lines->first()->unit_cost);
    }

    public function test_admin_sees_financials_in_show()
    {
        $quote = $this->createQuote($this->admin);
        $line = $quote->lines()->create([
            'product_service_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_cost' => 60,
            'sort_order' => 0,
        ]);
        $quote->recalculateTotal();

        $response = $this->actingAs($this->admin)->get(route('quotes.show', $quote));

        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('quotes/show')
                ->where('quote.total_profit', 40) // 100 - 60
                ->where('quote.lines.0.unit_cost', 60)
                ->where('quote.can.view_financials', true)
        );
    }

    public function test_sales_does_not_see_financials_in_show()
    {
        $quote = $this->createQuote($this->salesRep);
        $line = $quote->lines()->create([
            'product_service_id' => $this->product->id,
            'quantity' => 1,
            'unit_price' => 100,
            'unit_cost' => 60, // Should be hidden
            'sort_order' => 0,
        ]);
        $quote->recalculateTotal();

        $response = $this->actingAs($this->salesRep)->get(route('quotes.show', $quote));

        $response->assertInertia(
            fn(Assert $page) => $page
                ->component('quotes/show')
                ->missing('quote.total_profit')
                ->missing('quote.lines.0.unit_cost')
                ->where('quote.can.view_financials', false)
        );
    }
}
