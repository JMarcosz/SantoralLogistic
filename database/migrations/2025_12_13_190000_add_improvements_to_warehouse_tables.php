<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add improvements to inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->after('serial_number');
            $table->timestamp('received_at')->nullable()->after('expiration_date');
        });

        // Add receipt_number and shipping_order_id to warehouse_receipts
        Schema::table('warehouse_receipts', function (Blueprint $table) {
            $table->string('receipt_number', 30)->nullable()->unique()->after('id');
            $table->foreignId('shipping_order_id')->nullable()->after('customer_id')
                ->constrained('shipping_orders')->nullOnDelete();
        });

        // Add zone to locations
        Schema::table('locations', function (Blueprint $table) {
            $table->string('zone', 20)->nullable()->after('code');
            $table->index('zone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['expiration_date', 'received_at']);
        });

        Schema::table('warehouse_receipts', function (Blueprint $table) {
            $table->dropForeign(['shipping_order_id']);
            $table->dropColumn(['receipt_number', 'shipping_order_id']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['zone']);
            $table->dropColumn('zone');
        });
    }
};
