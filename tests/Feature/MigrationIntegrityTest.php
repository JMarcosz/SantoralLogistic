<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration integrity tests.
 *
 * Verify that all migrations run correctly, the schema is in the expected state,
 * and seeders execute without errors.
 *
 * Note: RefreshDatabase is applied globally via Pest.php, so migrate:fresh
 * has already run before each test.
 */

it('has all critical tables after migration', function () {
    expect(Schema::hasTable('inventory_items'))->toBeTrue();
    expect(Schema::hasTable('inventory_movements'))->toBeTrue();
    expect(Schema::hasTable('inventory_reservations'))->toBeTrue();
    expect(Schema::hasTable('warehouse_receipts'))->toBeTrue();
    expect(Schema::hasTable('warehouse_receipt_lines'))->toBeTrue();
    expect(Schema::hasTable('locations'))->toBeTrue();
    expect(Schema::hasTable('warehouses'))->toBeTrue();
    expect(Schema::hasTable('customers'))->toBeTrue();
});

it('has the correct columns on inventory_items after all migrations', function () {
    $columns = Schema::getColumnListing('inventory_items');

    // Columns that should exist after refactor migration
    expect($columns)->toContain('id');
    expect($columns)->toContain('warehouse_id');
    expect($columns)->toContain('customer_id');
    expect($columns)->toContain('item_code');
    expect($columns)->toContain('location_id');
    expect($columns)->toContain('warehouse_receipt_id');
    expect($columns)->toContain('warehouse_receipt_line_id');
    expect($columns)->toContain('description');
    expect($columns)->toContain('qty');
    expect($columns)->toContain('uom');
    expect($columns)->toContain('lot_number');
    expect($columns)->toContain('serial_number');
    expect($columns)->toContain('expiration_date');
    expect($columns)->toContain('received_at');

    // sku should NOT exist (was renamed to item_code in refactor migration)
    expect($columns)->not->toContain('sku');
});

it('can rollback and re-migrate the refactor_inventory_schema migration', function () {
    // SQLite has inherent limitations with dropColumn + indexes,
    // so this rollback test only runs on MySQL/MariaDB/PostgreSQL
    if (DB::connection()->getDriverName() === 'sqlite') {
        $this->markTestSkipped('Rollback test skipped on SQLite — run on MySQL to validate FK/index ordering.');
    }

    $rollbackCode = Artisan::call('migrate:rollback', [
        '--step' => 15,
        '--force' => true,
    ]);

    expect($rollbackCode)->toBe(0);

    $migrateCode = Artisan::call('migrate', ['--force' => true]);

    expect($migrateCode)->toBe(0);

    $columns = Schema::getColumnListing('inventory_items');
    expect($columns)->toContain('item_code');
    expect($columns)->not->toContain('sku');
});

it('can seed the database without errors', function () {
    $exitCode = Artisan::call('db:seed', ['--force' => true]);

    expect($exitCode)->toBe(0);
});
