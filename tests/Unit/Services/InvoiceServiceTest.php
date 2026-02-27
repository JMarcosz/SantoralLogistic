<?php

namespace Tests\Unit\Services;

use App\Exceptions\FiscalSequenceExhaustedException;
use App\Exceptions\NoFiscalSequenceAvailableException;
use App\Models\Customer;
use App\Models\FiscalSequence;
use App\Models\Invoice;
use App\Models\PreInvoice;
use App\Models\PreInvoiceLine;
use App\Services\FiscalNumberService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

describe('InvoiceService', function () {

    beforeEach(function () {
        // Create a mock for FiscalNumberService
        $this->fiscalNumberServiceMock = Mockery::mock(FiscalNumberService::class);
        $this->invoiceService = new InvoiceService($this->fiscalNumberServiceMock);
    });

    afterEach(function () {
        Mockery::close();
    });

    test('creates invoice from pre-invoice successfully', function () {
        // Create customer with valid fiscal data
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
            'ncf_type_default' => 'B01',
            'series' => null,
        ]);

        // Create pre-invoice with lines
        $preInvoice = PreInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => PreInvoice::STATUS_ISSUED,
            'subtotal_amount' => 1000.00,
            'tax_amount' => 180.00,
            'total_amount' => 1180.00,
            'invoiced_at' => null,
        ]);

        // Create pre-invoice lines
        PreInvoiceLine::factory()->create([
            'pre_invoice_id' => $preInvoice->id,
            'code' => 'ITEM-001',
            'description' => 'Test Item 1',
            'qty' => 2,
            'unit_price' => 500.00,
            'amount' => 1000.00,
            'tax_amount' => 180.00,
            'currency_code' => 'DOP',
            'sort_order' => 1,
        ]);

        // Mock FiscalNumberService to return a fixed NCF
        $expectedNcf = 'B01-00000000001';
        $this->fiscalNumberServiceMock
            ->shouldReceive('getNextNcf')
            ->once()
            ->with('B01', null)
            ->andReturn($expectedNcf);

        // Create invoice
        $invoice = $this->invoiceService->createFromPreInvoice($preInvoice);

        // Assertions
        expect($invoice)->toBeInstanceOf(Invoice::class);
        expect($invoice->ncf)->toBe($expectedNcf);
        expect($invoice->ncf_type)->toBe('B01');
        expect($invoice->customer_id)->toBe($customer->id);
        expect($invoice->pre_invoice_id)->toBe($preInvoice->id);
        expect($invoice->status)->toBe(Invoice::STATUS_ISSUED);

        // Verify amounts
        expect((float) $invoice->subtotal_amount)->toBe(1000.00);
        expect((float) $invoice->tax_amount)->toBe(180.00);
        expect((float) $invoice->total_amount)->toBe(1180.00);
        expect((float) $invoice->taxable_amount)->toBe(1000.00);
        expect((float) $invoice->exempt_amount)->toBe(0.00);

        // Verify lines were copied
        expect($invoice->lines)->toHaveCount(1);
        $line = $invoice->lines->first();
        expect($line->code)->toBe('ITEM-001');
        expect($line->description)->toBe('Test Item 1');
        expect((float) $line->qty)->toBe(2.0);
        expect((float) $line->unit_price)->toBe(500.00);

        // Verify pre-invoice was marked as invoiced
        expect($preInvoice->fresh()->invoiced_at)->not->toBeNull();
    });

    test('throws when pre-invoice already invoiced', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
            'ncf_type_default' => 'B01',
        ]);

        $preInvoice = PreInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => PreInvoice::STATUS_ISSUED,
            'invoiced_at' => now(), // Already invoiced
        ]);

        // Should throw exception
        $this->invoiceService->createFromPreInvoice($preInvoice);
    })->throws(\InvalidArgumentException::class, 'already been converted');

    test('throws when pre-invoice not in billable status', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
            'ncf_type_default' => 'B01',
        ]);

        $preInvoice = PreInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => PreInvoice::STATUS_DRAFT, // Not issued
            'invoiced_at' => null,
        ]);

        // Should throw exception
        $this->invoiceService->createFromPreInvoice($preInvoice);
    })->throws(\InvalidArgumentException::class, "must be in 'issued' status");

    test('requires customer fiscal data for invoice', function () {
        // Customer without tax_id
        $customer = Customer::factory()->create([
            'tax_id' => null,
            'tax_id_type' => 'RNC',
            'ncf_type_default' => 'B01',
        ]);

        $preInvoice = PreInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => PreInvoice::STATUS_ISSUED,
            'invoiced_at' => null,
        ]);

        // Should throw exception
        $this->invoiceService->createFromPreInvoice($preInvoice);
    })->throws(\InvalidArgumentException::class, 'must have a tax_id');

    test('requires customer tax_id_type for invoice', function () {
        // Customer without tax_id_type
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => null,
            'ncf_type_default' => 'B01',
        ]);

        $preInvoice = PreInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => PreInvoice::STATUS_ISSUED,
            'invoiced_at' => null,
        ]);

        // Should throw exception
        $this->invoiceService->createFromPreInvoice($preInvoice);
    })->throws(\InvalidArgumentException::class, 'must have a tax_id_type');

    test('propagates no fiscal sequence available exception', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
            'ncf_type_default' => 'B01',
        ]);

        $preInvoice = PreInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => PreInvoice::STATUS_ISSUED,
            'subtotal_amount' => 1000.00,
            'tax_amount' => 180.00,
            'total_amount' => 1180.00,
            'invoiced_at' => null,
        ]);

        // Mock to throw NoFiscalSequenceAvailableException
        $this->fiscalNumberServiceMock
            ->shouldReceive('getNextNcf')
            ->once()
            ->andThrow(new NoFiscalSequenceAvailableException('B01', null));

        // Should propagate exception
        $this->invoiceService->createFromPreInvoice($preInvoice);
    })->throws(NoFiscalSequenceAvailableException::class);

    test('propagates fiscal sequence exhausted exception', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
            'ncf_type_default' => 'B01',
        ]);

        $preInvoice = PreInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => PreInvoice::STATUS_ISSUED,
            'subtotal_amount' => 1000.00,
            'tax_amount' => 180.00,
            'total_amount' => 1180.00,
            'invoiced_at' => null,
        ]);

        // Create a dummy fiscal sequence for the exception
        $sequence = FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01-00000000001',
            'ncf_to' => 'B01-00000000001',
            'current_ncf' => 'B01-00000000001',
            'valid_from' => now(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
        ]);

        // Mock to throw FiscalSequenceExhaustedException
        $this->fiscalNumberServiceMock
            ->shouldReceive('getNextNcf')
            ->once()
            ->andThrow(FiscalSequenceExhaustedException::forSequence($sequence));

        // Should propagate exception
        $this->invoiceService->createFromPreInvoice($preInvoice);
    })->throws(FiscalSequenceExhaustedException::class);
});
