<?php

namespace Tests\Feature;

use App\Models\FiscalSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FiscalSequenceAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the permission
        Permission::create([
            'name' => 'fiscal_sequences.manage',
            'guard_name' => 'web',
        ]);
    }

    /** @test */
    public function user_without_permission_receives_403_on_index()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/fiscal-sequences');

        $response->assertForbidden();
    }

    /** @test */
    public function user_without_permission_receives_403_on_create()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/fiscal-sequences/create');

        $response->assertForbidden();
    }

    /** @test */
    public function user_without_permission_receives_403_on_store()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/fiscal-sequences', [
            'ncf_type' => 'B01',
            'series' => '001',
            'ncf_from' => 'B01001000000001',
            'ncf_to' => 'B01001000001000',
            'valid_from' => now()->format('Y-m-d'),
            'valid_to' => now()->addYear()->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_without_permission_receives_403_on_edit()
    {
        $user = User::factory()->create();
        $sequence = FiscalSequence::factory()->create();

        $response = $this->actingAs($user)->get("/admin/fiscal-sequences/{$sequence->id}/edit");

        $response->assertForbidden();
    }

    /** @test */
    public function user_without_permission_receives_403_on_update()
    {
        $user = User::factory()->create();
        $sequence = FiscalSequence::factory()->create();

        $response = $this->actingAs($user)->put("/admin/fiscal-sequences/{$sequence->id}", [
            'ncf_type' => $sequence->ncf_type,
            'series' => $sequence->series,
            'ncf_from' => $sequence->ncf_from,
            'ncf_to' => $sequence->ncf_to,
            'valid_from' => $sequence->valid_from->format('Y-m-d'),
            'valid_to' => $sequence->valid_to->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_without_permission_receives_403_on_destroy()
    {
        $user = User::factory()->create();
        $sequence = FiscalSequence::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/fiscal-sequences/{$sequence->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function user_with_permission_can_access_index()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('fiscal_sequences.manage');

        $response = $this->actingAs($user)->get('/admin/fiscal-sequences');

        $response->assertOk();
    }

    /** @test */
    public function user_with_permission_can_access_create()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('fiscal_sequences.manage');

        $response = $this->actingAs($user)->get('/admin/fiscal-sequences/create');

        $response->assertOk();
    }

    /** @test */
    public function user_with_permission_can_store()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('fiscal_sequences.manage');

        $response = $this->actingAs($user)->post('/admin/fiscal-sequences', [
            'ncf_type' => 'B01',
            'series' => '001',
            'ncf_from' => 'B01001000000001',
            'ncf_to' => 'B01001000001000',
            'valid_from' => now()->format('Y-m-d'),
            'valid_to' => now()->addYear()->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/fiscal-sequences');
        $this->assertDatabaseHas('fiscal_sequences', [
            'ncf_type' => 'B01',
            'series' => '001',
        ]);
    }

    /** @test */
    public function user_with_permission_can_edit()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('fiscal_sequences.manage');
        $sequence = FiscalSequence::factory()->create();

        $response = $this->actingAs($user)->get("/admin/fiscal-sequences/{$sequence->id}/edit");

        $response->assertOk();
    }

    /** @test */
    public function user_with_permission_can_update()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('fiscal_sequences.manage');
        $sequence = FiscalSequence::factory()->create();

        $response = $this->actingAs($user)->put("/admin/fiscal-sequences/{$sequence->id}", [
            'ncf_type' => $sequence->ncf_type,
            'series' => '002', // Changed
            'ncf_from' => $sequence->ncf_from,
            'ncf_to' => $sequence->ncf_to,
            'valid_from' => $sequence->valid_from->format('Y-m-d'),
            'valid_to' => $sequence->valid_to->format('Y-m-d'),
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/fiscal-sequences');
        $this->assertDatabaseHas('fiscal_sequences', [
            'id' => $sequence->id,
            'series' => '002',
        ]);
    }

    /** @test */
    public function user_with_permission_can_destroy()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('fiscal_sequences.manage');
        $sequence = FiscalSequence::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/fiscal-sequences/{$sequence->id}");

        $response->assertRedirect('/admin/fiscal-sequences');

        // Should be soft deleted (is_active = false)
        $this->assertDatabaseHas('fiscal_sequences', [
            'id' => $sequence->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function unauthenticated_user_redirects_to_login()
    {
        $response = $this->get('/admin/fiscal-sequences');

        $response->assertRedirect('/login');
    }
}
