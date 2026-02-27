<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\DgiiExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('DgiiExportService', function () {

    beforeEach(function () {
        $this->dgiiService = new DgiiExportService();
    });

    test('generates 607 with correct basic format', function () {
        // Create customer with tax data
        $customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
        ]);

        // Create issued invoice
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000001',
            'ncf_type' => 'B01',
            'issue_date' => Carbon::create(2025, 1, 15),
            'status' => Invoice::STATUS_ISSUED,
            'subtotal_amount' => 1000.00,
            'tax_amount' => 180.00,
            'total_amount' => 1180.00,
            'taxable_amount' => 1000.00,
            'exempt_amount' => 0.00,
        ]);

        // Generate 607 for January 2025
        $periodStart = Carbon::create(2025, 1, 1);
        $periodEnd = Carbon::create(2025, 1, 31);

        $output = $this->dgiiService->generate607($periodStart, $periodEnd);

        // Assertions
        expect($output)->not->toBeEmpty();

        // Should have one line
        expect(substr_count($output, "\n"))->toBe(0); // Single line (no newline at end for 1 invoice)

        // Verify pipe-delimited format
        $fields = explode('|', $output);
        expect($fields)->toHaveCount(16); // 607 has 16 fields

        // Verify specific fields
        expect($fields[0])->toBe('123456789'); // RNC
        expect($fields[1])->toBe('1'); // Tax ID Type (RNC = 1)
        expect($fields[2])->toBe('B0100000000001'); // NCF without dashes
        expect($fields[4])->toBe('01'); // Income type (operations)
        expect($fields[5])->toBe('15/01/2025'); // Date DD/MM/YYYY
        expect($fields[7])->toBe('1000.00'); // Invoiced amount (taxable)
        expect($fields[8])->toBe('180.00'); // ITBIS
    });

    test('generates 608 with cancelled invoices only', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
        ]);

        // Create issued invoice (should NOT  be in 608)
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000001',
            'status' => Invoice::STATUS_ISSUED,
            'issue_date' => Carbon::create(2025, 1, 15),
        ]);

        // Create cancelled invoice (SHOULD be in 608)
        $cancelledInvoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000002',
            'status' => Invoice::STATUS_CANCELLED,
            'issue_date' => Carbon::create(2025, 1, 10),
            'cancelled_at' => Carbon::create(2025, 1, 20),
            'cancellation_reason' => 'Error en factura',
        ]);

        // Generate 608 for January 2025
        $periodStart = Carbon::create(2025, 1, 1);
        $periodEnd = Carbon::create(2025, 1, 31);

        $output = $this->dgiiService->generate608($periodStart, $periodEnd);

        // Assertions
        expect($output)->not->toBeEmpty();

        // Should only include cancelled invoice
        expect($output)->toContain('B0100000000002');
        expect($output)->not->toContain('B0100000000001');

        // Verify 608 format (NCF|FechaComprobante|TipoAnulacion)
        $fields = explode('|', $output);
        expect($fields)->toHaveCount(3);
        expect($fields[0])->toBe('B0100000000002');
        expect($fields[1])->toBe('10/01/2025'); // Issue date
        expect($fields[2])->toBeIn(['01', '02', '03', '04', '05', '06', '07', '08', '09', '10']); // Valid cancellation type
    });

    test('skips invoices outside period', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
        ]);

        // Invoice inside period
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000001',
            'status' => Invoice::STATUS_ISSUED,
            'issue_date' => Carbon::create(2025, 1, 15),
        ]);

        // Invoice outside period (before)
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000002',
            'status' => Invoice::STATUS_ISSUED,
            'issue_date' => Carbon::create(2024, 12, 31),
        ]);

        // Invoice outside period (after)
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000003',
            'status' => Invoice::STATUS_ISSUED,
            'issue_date' => Carbon::create(2025, 2, 1),
        ]);

        // Generate 607 for January 2025 only
        $periodStart = Carbon::create(2025, 1, 1);
        $periodEnd = Carbon::create(2025, 1, 31);

        $output = $this->dgiiService->generate607($periodStart, $periodEnd);

        // Should only include invoice from January
        expect($output)->toContain('B0100000000001');
        expect($output)->not->toContain('B0100000000002');
        expect($output)->not->toContain('B0100000000003');

        // Should have only one line
        expect(substr_count($output, "\n"))->toBe(0);
    });

    test('validates required customer tax data', function () {
        // Create invoice with total > RD$250,000 but no customer tax_id
        $customer = Customer::factory()->create([
            'tax_id' => null, // Missing tax_id
            'tax_id_type' => 'RNC',
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000001',
            'status' => Invoice::STATUS_ISSUED,
            'total_amount' => 300000.00, // > RD$250,000
        ]);

        $invoices = collect([$invoice]);
        $errors = $this->dgiiService->validateInvoicesForExport($invoices);

        // Should have validation error
        expect($errors)->not->toBeEmpty();
        expect($errors[0])->toContain('RNC/Cédula required');
        expect($errors[0])->toContain('250,000');
    });

    test('handles zero tax amounts and exempt amounts', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
        ]);

        // Invoice with zero tax (exempt sale)
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000001',
            'status' => Invoice::STATUS_ISSUED,
            'issue_date' => Carbon::create(2025, 1, 15),
            'subtotal_amount' => 1000.00,
            'tax_amount' => 0.00, // Zero tax
            'total_amount' => 1000.00,
            'taxable_amount' => 0.00,
            'exempt_amount' => 1000.00, // All exempt
        ]);

        $periodStart = Carbon::create(2025, 1, 1);
        $periodEnd = Carbon::create(2025, 1, 31);

        $output = $this->dgiiService->generate607($periodStart, $periodEnd);

        // Verify zero amounts are formatted correctly
        $fields = explode('|', $output);
        expect($fields[7])->toBe('0.00'); // Invoiced amount (taxable = 0)
        expect($fields[8])->toBe('0.00'); // ITBIS = 0
    });

    test('returns empty string when no invoices in period', function () {
        // No invoices created
        $periodStart = Carbon::create(2025, 1, 1);
        $periodEnd = Carbon::create(2025, 1, 31);

        $output607 = $this->dgiiService->generate607($periodStart, $periodEnd);
        $output608 = $this->dgiiService->generate608($periodStart, $periodEnd);

        expect($output607)->toBe('');
        expect($output608)->toBe('');
    });

    test('formats ncf correctly removing dashes', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000001', // NCF with dash
            'status' => Invoice::STATUS_ISSUED,
            'issue_date' => Carbon::create(2025, 1, 15),
        ]);

        $periodStart = Carbon::create(2025, 1, 1);
        $periodEnd = Carbon::create(2025, 1, 31);

        $output = $this->dgiiService->generate607($periodStart, $periodEnd);

        // NCF should be without dashes
        expect($output)->toContain('B0100000000001');
        expect($output)->not->toContain('B01-00000000001');
    });

    test('formats date as DD/MM/YYYY', function () {
        $customer = Customer::factory()->create([
            'tax_id' => '123456789',
            'tax_id_type' => 'RNC',
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'ncf' => 'B01-00000000001',
            'status' => Invoice::STATUS_ISSUED,
            'issue_date' => Carbon::create(2025, 3, 5), // March 5, 2025
        ]);

        $periodStart = Carbon::create(2025, 3, 1);
        $periodEnd = Carbon::create(2025, 3, 31);

        $output = $this->dgiiService->generate607($periodStart, $periodEnd);

        // Date should be formatted as DD/MM/YYYY
        expect($output)->toContain('05/03/2025');
    });
});
