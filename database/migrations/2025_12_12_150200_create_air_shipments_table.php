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
        Schema::create('air_shipments', function (Blueprint $table) {
            $table->id();

            // 1:1 relationship with shipping_orders (unique constraint enforces 1:1)
            $table->foreignId('shipping_order_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Air Waybill numbers
            $table->string('mawb_number')->nullable(); // Master Air Waybill
            $table->string('hawb_number')->nullable(); // House Air Waybill

            // Airline and flight info
            $table->string('airline_name')->nullable();
            $table->string('flight_number')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('air_shipments');
    }
};
