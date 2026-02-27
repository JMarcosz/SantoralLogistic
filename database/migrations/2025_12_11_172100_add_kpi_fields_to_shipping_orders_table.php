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
            // KPI fields for SLA/OTIF tracking
            $table->boolean('delivered_on_time')->nullable()->after('status');
            $table->boolean('delivered_in_full')->nullable()->after('delivered_on_time');
            $table->integer('delivery_delay_days')->nullable()->after('delivered_in_full');

            // Index for reporting queries
            $table->index('delivered_on_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->dropIndex(['delivered_on_time']);
            $table->dropColumn([
                'delivered_on_time',
                'delivered_in_full',
                'delivery_delay_days',
            ]);
        });
    }
};
