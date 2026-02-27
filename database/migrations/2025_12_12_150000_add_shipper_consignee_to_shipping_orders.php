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
        Schema::table('shipping_orders', function (Blueprint $table) {
            // Shipper - operational party who ships the goods
            $table->foreignId('shipper_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            // Consignee - operational party who receives the goods
            $table->foreignId('consignee_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            // Indexes for query performance
            $table->index(['shipper_id']);
            $table->index(['consignee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->dropForeign(['shipper_id']);
            $table->dropForeign(['consignee_id']);
            $table->dropIndex(['shipper_id']);
            $table->dropIndex(['consignee_id']);
            $table->dropColumn(['shipper_id', 'consignee_id']);
        });
    }
};
