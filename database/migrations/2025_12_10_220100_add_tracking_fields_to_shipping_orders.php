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
            // Add planned/actual datetime fields for tracking
            $table->dateTimeTz('planned_departure_at')->nullable()->after('delivery_date');
            $table->dateTimeTz('planned_arrival_at')->nullable()->after('planned_departure_at');
            $table->dateTimeTz('actual_departure_at')->nullable()->after('planned_arrival_at');
            $table->dateTimeTz('actual_arrival_at')->nullable()->after('actual_departure_at');

            // Add is_active flag
            $table->boolean('is_active')->default(true)->after('status');

            // Index for active orders
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'planned_departure_at',
                'planned_arrival_at',
                'actual_departure_at',
                'actual_arrival_at',
                'is_active',
            ]);
        });
    }
};
