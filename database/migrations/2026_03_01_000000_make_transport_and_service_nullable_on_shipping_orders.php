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
            // allow nulls for transport_mode_id and service_type_id
            $table->foreignId('transport_mode_id')
                ->nullable()
                ->change();

            $table->foreignId('service_type_id')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->foreignId('transport_mode_id')
                ->nullable(false)
                ->change();

            $table->foreignId('service_type_id')
                ->nullable(false)
                ->change();
        });
    }
};
