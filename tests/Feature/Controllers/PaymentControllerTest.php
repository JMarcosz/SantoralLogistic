<?php

namespace Tests\Feature\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create the permissions needed for tests
    Permission::firstOrCreate(['name' => 'payments.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.delete', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.post', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'payments.void', 'guard_name' => 'web']);
});

test('index requires authentication', function () {
    $this->get('/payments')
        ->assertRedirect('/login');
});

test('index requires payments.view permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/payments')
        ->assertStatus(403);
});

test('user with payments.view can see payment list', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.view');

    $this->actingAs($user)
        ->get('/payments')
        ->assertStatus(200);
});

test('store requires payments.create permission', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->post('/payments', [
            'type' => 'inbound',
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'currency_code' => 'DOP',
            'exchange_rate' => 1,
        ])
        ->assertStatus(403);
});

test('user with payments.create can create payment', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.create');
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 500,
        'balance' => 500,
        'status' => 'issued',
        'currency_code' => 'DOP',
    ]);

    $this->actingAs($user)
        ->post('/payments', [
            'type' => 'inbound',
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'currency_code' => 'DOP',
            'exchange_rate' => 1,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount_applied' => 100],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('payments', [
        'customer_id' => $customer->id,
        'amount' => 100,
        'status' => 'draft',
    ]);
});

test('store validates allocation sum does not exceed amount', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.create');
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 1000,
        'balance' => 1000,
        'status' => 'issued',
        'currency_code' => 'DOP',
    ]);

    $response = $this->actingAs($user)
        ->post('/payments', [
            'type' => 'inbound',
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100, // Only 100
            'currency_code' => 'DOP',
            'exchange_rate' => 1,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount_applied' => 500], // Trying to apply 500
            ],
        ]);

    $response->assertSessionHas('error');
});

test('store validates withholdings do not exceed amount', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.create');
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)
        ->post('/payments', [
            'type' => 'inbound',
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'currency_code' => 'DOP',
            'exchange_rate' => 1,
            'isr_withholding_amount' => 80,
            'itbis_withholding_amount' => 50, // Total: 130 > 100
        ]);

    $response->assertSessionHas('error');
});

test('post requires payments.post permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.view');
    $payment = Payment::factory()->create(['status' => 'draft']);

    $this->actingAs($user)
        ->post("/payments/{$payment->id}/post")
        ->assertStatus(403);
});

test('user with payments.post can post payment', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.view', 'payments.post');
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $payment = Payment::factory()->create([
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'status' => 'draft',
        'amount' => 100,
    ]);

    $this->actingAs($user)
        ->post("/payments/{$payment->id}/post")
        ->assertRedirect();

    $payment->refresh();
    expect($payment->status)->toBe('posted');
});

test('void requires payments.void permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.view');
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $payment = Payment::factory()->create([
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'status' => 'posted',
    ]);

    $this->actingAs($user)
        ->post("/payments/{$payment->id}/void", ['void_reason' => 'Test'])
        ->assertStatus(403);
});

test('delete requires payments.delete permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.view');
    $payment = Payment::factory()->create(['status' => 'draft']);

    $this->actingAs($user)
        ->delete("/payments/{$payment->id}")
        ->assertStatus(403);
});

test('user with payments.delete can delete draft payment', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.view', 'payments.delete');
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $payment = Payment::factory()->create([
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->delete("/payments/{$payment->id}")
        ->assertRedirect('/payments');

    $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
});

test('user with payments.view can download pdf', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payments.view');
    $customer = Customer::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $payment = Payment::factory()->create([
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'status' => 'posted',
    ]);

    $this->actingAs($user)
        ->get("/payments/{$payment->id}/pdf")
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
});
