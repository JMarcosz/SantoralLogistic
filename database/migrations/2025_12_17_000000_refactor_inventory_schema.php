<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. inventory_items: Add item_code, copy sku, drop sku
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('item_code', 100)->after('warehouse_receipt_id')->nullable();
        });

        DB::statement("UPDATE inventory_items SET item_code = sku WHERE sku IS NOT NULL");

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('item_code', 100)->nullable(false)->change();
            // Drop indexes using array syntax which Laravel translates to index name
             $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['warehouse_id', 'sku']);
            $table->dropIndex(['customer_id', 'sku']);
            $table->dropColumn('sku');

            // Add new indexes
            $table->index(['warehouse_id', 'item_code']);
            $table->index(['customer_id', 'item_code']);
            $table->foreign('warehouse_id')
          ->references('id')
          ->on('warehouses')
          ->cascadeOnDelete();
        });

        // 2. Add warehouse_receipt_line_id to inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('warehouse_receipt_line_id')
                ->nullable()
                ->after('warehouse_receipt_id')
                ->constrained('warehouse_receipt_lines')
                ->nullOnDelete();
        });

        // 3. warehouse_receipt_lines: Add item_code, copy sku, drop sku
        Schema::table('warehouse_receipt_lines', function (Blueprint $table) {
            $table->string('item_code', 100)->after('warehouse_receipt_id')->nullable();
        });

        DB::statement("UPDATE warehouse_receipt_lines SET item_code = sku WHERE sku IS NOT NULL");

        Schema::table('warehouse_receipt_lines', function (Blueprint $table) {
            $table->string('item_code', 100)->nullable(false)->change();
            $table->dropIndex(['sku']);
            $table->dropColumn('sku');
            $table->index('item_code');
        });

        // 4. Create "RECEIVING" location for each warehouse if not exists
        $warehouses = DB::table('warehouses')->get();
        foreach ($warehouses as $warehouse) {
            $exists = DB::table('locations')
                ->where('warehouse_id', $warehouse->id)
                ->where('code', 'RECEIVING')
                ->exists();

            if (!$exists) {
                DB::table('locations')->insert([
                    'warehouse_id' => $warehouse->id,
                    'code' => 'RECEIVING',
                    'type' => 'dock',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse 3: Add sku, copy item_code, drop item_code
        Schema::table('warehouse_receipt_lines', function (Blueprint $table) {
            $table->string('sku', 100)->after('warehouse_receipt_id')->nullable();
        });

        DB::statement("UPDATE warehouse_receipt_lines SET sku = item_code WHERE item_code IS NOT NULL");

        Schema::table('warehouse_receipt_lines', function (Blueprint $table) {
            $table->string('sku', 100)->nullable(false)->change();
            $table->dropIndex(['item_code']);
            $table->dropColumn('item_code');
            $table->index('sku');
        });

        // Reverse 2
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_receipt_line_id']);
            $table->dropColumn('warehouse_receipt_line_id');
        });

        // Reverse 1: Add sku, copy item_code, drop item_code
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('sku', 100)->after('warehouse_receipt_id')->nullable();
        });

        DB::statement("UPDATE inventory_items SET sku = item_code WHERE item_code IS NOT NULL");

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('sku', 100)->nullable(false)->change();
            $table->dropIndex(['warehouse_id', 'item_code']);
            $table->dropIndex(['customer_id', 'item_code']);
            $table->dropColumn('item_code');

            $table->index(['warehouse_id', 'sku']);
            $table->index(['customer_id', 'sku']);
        });
    }
};
