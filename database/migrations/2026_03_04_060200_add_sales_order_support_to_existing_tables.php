<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds sales_order_id to inventory_reservations and invoices,
     * line_type to quote_lines, and product_service_id to
     * warehouse_receipt_lines and inventory_items.
     */
    public function up(): void
    {
        // 1. inventory_reservations: add sales_order_id as alternative to shipping_order_id
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->foreignId('sales_order_id')
                ->nullable()
                ->after('shipping_order_id')
                ->constrained('sales_orders')
                ->cascadeOnDelete();
        });

        // Make shipping_order_id nullable (was NOT NULL)
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_order_id')->nullable()->change();
        });

        // 2. invoices: add sales_order_id
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sales_order_id')
                ->nullable()
                ->after('shipping_order_id')
                ->constrained('sales_orders')
                ->nullOnDelete();
            $table->index('sales_order_id');
        });

        // 3. quote_lines: add line_type to distinguish product vs service
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->string('line_type', 20)
                ->default('service')
                ->after('product_service_id');
        });

        // 4. warehouse_receipt_lines: add product_service_id FK
        Schema::table('warehouse_receipt_lines', function (Blueprint $table) {
            $table->foreignId('product_service_id')
                ->nullable()
                ->after('warehouse_receipt_id')
                ->constrained('products_services')
                ->nullOnDelete();
        });

        // 5. inventory_items: add product_service_id FK
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('product_service_id')
                ->nullable()
                ->after('warehouse_receipt_line_id')
                ->constrained('products_services')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 5. inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['product_service_id']);
            $table->dropColumn('product_service_id');
        });

        // 4. warehouse_receipt_lines
        Schema::table('warehouse_receipt_lines', function (Blueprint $table) {
            $table->dropForeign(['product_service_id']);
            $table->dropColumn('product_service_id');
        });

        // 3. quote_lines
        Schema::table('quote_lines', function (Blueprint $table) {
            $table->dropColumn('line_type');
        });

        // 2. invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropIndex(['sales_order_id']);
            $table->dropColumn('sales_order_id');
        });

        // 1. inventory_reservations: restore shipping_order_id as NOT NULL, drop sales_order_id
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_order_id')->nullable(false)->change();
        });

        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn('sales_order_id');
        });
    }
};
