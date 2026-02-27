<?php

namespace Tests\Feature\Quotes;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class QuoteRbacTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $salesRep1;
    protected $salesRep2;

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

        // Setup Users
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->salesRep1 = User::factory()->create();
        $this->salesRep1->assignRole($salesRole);

        $this->salesRep2 = User::factory()->create();
        $this->salesRep2->assignRole($salesRole);
    }

    public function test_admin_can_view_all_quotes()
    {
        // Create quotes for different users
        Quote::factory()->create(['created_by' => $this->salesRep1->id]);
        Quote::factory()->create(['created_by' => $this->salesRep2->id]);

        $this->actingAs($this->admin)
            ->get(route('quotes.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('quotes/index')
                    ->has('quotes.data', 2)
            );
    }

    public function test_sales_rep_can_only_view_own_quotes_in_index()
    {
        // Create 2 quotes for rep1 and 1 quote for rep2
        Quote::factory()->count(2)->create(['created_by' => $this->salesRep1->id]);
        Quote::factory()->count(1)->create(['created_by' => $this->salesRep2->id]);

        $this->actingAs($this->salesRep1)
            ->get(route('quotes.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('quotes/index')
                    ->has('quotes.data', 2) // Should only see their 2 quotes
            );

        $this->actingAs($this->salesRep2)
            ->get(route('quotes.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('quotes/index')
                    ->has('quotes.data', 1) // Should only see their 1 quote
            );
    }

    public function test_sales_rep_cannot_view_others_quote_detail()
    {
        $quoteOfRep2 = Quote::factory()->create(['created_by' => $this->salesRep2->id]);

        $this->actingAs($this->salesRep1)
            ->get(route('quotes.show', $quoteOfRep2))
            ->assertStatus(403);
    }

    public function test_sales_rep_cannot_update_others_quote()
    {
        $quoteOfRep2 = Quote::factory()->draft()->create(['created_by' => $this->salesRep2->id]);

        $this->actingAs($this->salesRep1)
            ->put(route('quotes.update', $quoteOfRep2), ['notes' => 'Hacked'])
            ->assertStatus(403);
    }

    public function test_sales_rep_cannot_delete_others_quote()
    {
        $quoteOfRep2 = Quote::factory()->draft()->create(['created_by' => $this->salesRep2->id]);

        $this->actingAs($this->salesRep1)
            ->delete(route('quotes.destroy', $quoteOfRep2))
            ->assertStatus(403);
    }
}
