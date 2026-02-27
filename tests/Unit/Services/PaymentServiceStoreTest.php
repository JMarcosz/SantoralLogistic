<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

test('creates payment with single invoice allocation correctly', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    // Create an invoice with balance
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'DOP',
        'total_amount' => 1000.00,
        'balance' => 1000.00,
        'payment_status' => 'pending',
        'status' => 'issued',
    ]);

    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 500.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 1,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 500.00,
            ],
        ],
    ];

    $payment = $paymentService->store($data, $user);

    expect($payment)->toBeInstanceOf(Payment::class);
    expect((float) $payment->amount)->toBe(500.00);
    expect($payment->allocations)->toHaveCount(1);
});

test('updates invoice balance after payment allocation', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'DOP',
        'total_amount' => 1000.00,
        'balance' => 1000.00,
        'payment_status' => 'pending',
        'status' => 'issued',
    ]);

    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 400.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 1,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 400.00,
            ],
        ],
    ];

    $paymentService->store($data, $user);
    $invoice->refresh();

    expect((float) $invoice->amount_paid)->toBe(400.00);
    expect((float) $invoice->balance)->toBe(600.00);
});

test('sets invoice status to paid when balance is zero', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'DOP',
        'total_amount' => 500.00,
        'balance' => 500.00,
        'payment_status' => 'pending',
        'status' => 'issued',
    ]);

    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 500.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 1,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 500.00,
            ],
        ],
    ];

    $paymentService->store($data, $user);
    $invoice->refresh();

    expect($invoice->payment_status)->toBe('paid');
    expect((float) $invoice->balance)->toBe(0.00);
});

test('sets invoice status to partial when partially paid', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'DOP',
        'total_amount' => 1000.00,
        'balance' => 1000.00,
        'payment_status' => 'pending',
        'status' => 'issued',
    ]);

    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 300.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 1,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 300.00,
            ],
        ],
    ];

    $paymentService->store($data, $user);
    $invoice->refresh();

    expect($invoice->payment_status)->toBe('partial');
    expect((float) $invoice->balance)->toBe(700.00);
});

test('throws exception when allocation exceeds invoice balance', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'DOP',
        'total_amount' => 500.00,
        'balance' => 500.00,
        'payment_status' => 'pending',
        'status' => 'issued',
    ]);

    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 600.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 1,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 600.00,
            ],
        ],
    ];

    $paymentService->store($data, $user);
})->throws(InvalidArgumentException::class, 'excede el saldo');

test('converts currency correctly for multi-currency payments', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    // USD invoice
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'USD',
        'total_amount' => 100.00,
        'balance' => 100.00,
        'payment_status' => 'pending',
        'status' => 'issued',
    ]);

    // DOP payment at exchange rate 58.50
    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 5850.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 58.50,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 5850.00,
            ],
        ],
    ];

    $paymentService->store($data, $user);
    $invoice->refresh();

    // Invoice should be fully paid (5850 DOP / 58.50 = 100 USD)
    expect($invoice->payment_status)->toBe('paid');
    expect((float) $invoice->balance)->toBe(0.00);
});

test('rolls back entirely on validation failure', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'DOP',
        'total_amount' => 200.00,
        'balance' => 200.00,
        'payment_status' => 'pending',
        'status' => 'issued',
    ]);

    $initialPaymentCount = Payment::count();
    $initialBalance = $invoice->balance;

    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 500.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 1,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 500.00,
            ],
        ],
    ];

    try {
        $paymentService->store($data, $user);
    } catch (InvalidArgumentException $e) {
        // Expected
    }

    expect(Payment::count())->toBe($initialPaymentCount);
    $invoice->refresh();
    expect((float) $invoice->balance)->toBe((float) $initialBalance);
});

test('throws exception for cancelled invoice', function () {
    $paymentService = new PaymentService();
    $user = User::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
    $customer = Customer::factory()->create();

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'currency_code' => 'DOP',
        'total_amount' => 500.00,
        'balance' => 500.00,
        'payment_status' => 'pending',
        'status' => 'cancelled',
    ]);

    $data = [
        'type' => 'inbound',
        'customer_id' => $customer->id,
        'payment_method_id' => $paymentMethod->id,
        'payment_date' => now()->toDateString(),
        'amount' => 100.00,
        'currency_code' => 'DOP',
        'exchange_rate' => 1,
        'allocations' => [
            [
                'invoice_id' => $invoice->id,
                'amount_applied' => 100.00,
            ],
        ],
    ];

    $paymentService->store($data, $user);
})->throws(InvalidArgumentException::class, 'cancelada');
