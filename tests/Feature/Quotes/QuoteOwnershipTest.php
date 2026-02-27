<?php

namespace Tests\Feature\Quotes;

use App\Models\Quote;
use App\Models\User;
use App\Enums\QuoteStatus;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    public function test_sales_can_manage_own_quote()
    {
        // Sales User A
        $user = User::factory()->create();
        $user->assignRole('sales');

        // Give permissions to perform actions (assuming Sales has these permissions for testing ownership logic)
        $user->givePermissionTo([
            'quotes.create',
            'quotes.view',
            'quotes.update',
            'quotes.delete',
            'quotes.send',
            'quotes.approve',
            'quotes.reject',
            'quotes.convert_to_shipping_order'
        ]);

        // Quote owned by User A
        $quote = Quote::factory()->create([
            'created_by' => $user->id,
            'status' => 'draft'
        ]);

        // View
        $this->actingAs($user)->get(route('quotes.show', $quote))->assertOk();

        // Update
        $this->actingAs($user)->put(route('quotes.update', $quote), [
            'customer_id' => $quote->customer_id, // Minimal payload
        ])->assertRedirect(); // Success redirect

        // Send (Transition to Sent)
        // Note: The policy check happens before the controller logic, so we can test the policy gate.
        // We use a mock or just rely on the fact that if policy fails it returns 403.
        // For 'send' there is usually a route/controller action. Let's assume there is one.
        // If no route exists yet for 'send', we should test the Policy directly to be sure.

        $this->assertTrue($user->can('send', $quote));
    }

    public function test_sales_cannot_manage_others_quote()
    {
        // Sales User A
        $userA = User::factory()->create();
        $userA->assignRole('sales');
        $userA->givePermissionTo([
            'quotes.view',
            'quotes.update',
            'quotes.delete',
            'quotes.send',
            'quotes.approve',
            'quotes.reject',
            'quotes.convert_to_shipping_order'
        ]);

        // Sales User B (Owner)
        $userB = User::factory()->create();

        // Quote owned by User B, NOT assigned to User A
        $quote = Quote::factory()->create([
            'created_by' => $userB->id,
            'sales_rep_id' => null, // Explicitly not assigned to anyone
            'status' => 'draft'
        ]);

        // Double check ownership logic before testing policy
        $this->assertFalse($userA->id === $quote->created_by);
        $this->assertFalse($userA->id === $quote->sales_rep_id);

        // View -> 403
        $this->actingAs($userA)->get(route('quotes.show', $quote))->assertForbidden();

        // Update -> 403
        $this->actingAs($userA)->put(route('quotes.update', $quote), [])->assertForbidden();

        // Delete -> 403
        $this->actingAs($userA)->delete(route('quotes.destroy', $quote))->assertForbidden();

        // Policy Check for other actions
        $this->assertFalse($userA->can('send', $quote));

        // Set to Sent for approve/reject check
        $quote->update(['status' => 'sent']);
        $this->assertFalse($userA->can('approve', $quote));
        $this->assertFalse($userA->can('reject', $quote));

        // Set to Approved for convert check
        $quote->update(['status' => 'approved']);
        $this->assertFalse($userA->can('convertToShippingOrder', $quote));
    }

    public function test_sales_can_manage_assigned_quote()
    {
        // Sales User A (Assigned Rep)
        $userA = User::factory()->create();
        $userA->assignRole('sales');
        $userA->givePermissionTo([
            'quotes.view',
            'quotes.update',
            'quotes.send'
        ]);

        // User B (Creator)
        $userB = User::factory()->create();

        // Quote created by B but assigned to A
        $quote = Quote::factory()->create([
            'created_by' => $userB->id,
            'sales_rep_id' => $userA->id, // Assigned to A
            'status' => 'draft'
        ]);

        // View -> OK
        $this->actingAs($userA)->get(route('quotes.show', $quote))->assertOk();

        // Update -> OK
        $this->actingAs($userA)->put(route('quotes.update', $quote), ['customer_id' => $quote->customer_id])->assertRedirect();

        // Send -> OK
        $this->assertTrue($userA->can('send', $quote));
    }

    public function test_admin_can_manage_any_quote()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        // Ensure roles/permissions are fresh
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $admin->refresh();

        $userB = User::factory()->create();
        $quote = Quote::factory()->create([
            'created_by' => $userB->id,
            'status' => 'draft'
        ]);

        // Admin can view/update/delete others
        $this->actingAs($admin)->get(route('quotes.show', $quote))->assertOk();
        $this->assertTrue($admin->can('update', $quote));
        $this->assertTrue($admin->can('delete', $quote));
        $this->assertTrue($admin->can('send', $quote));

        // Admin can approve/reject sent quotes
        $quote->update(['status' => 'sent']);
        $this->assertTrue($admin->can('approve', $quote));
        $this->assertTrue($admin->can('reject', $quote));

        // Admin can convert approved quotes
        $quote->update(['status' => 'approved']);
        $this->assertTrue($admin->can('convertToShippingOrder', $quote));
    }
}
