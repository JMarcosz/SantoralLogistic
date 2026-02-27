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
        Schema::create('ocean_shipments', function (Blueprint $table) {
            $table->id();

            // 1:1 relationship with shipping_orders (unique constraint enforces 1:1)
            $table->foreignId('shipping_order_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Bill of Lading numbers
            $table->string('mbl_number')->nullable(); // Master Bill of Lading
            $table->string('hbl_number')->nullable(); // House Bill of Lading

            // Carrier and vessel info
            $table->string('carrier_name')->nullable();
            $table->string('vessel_name')->nullable();
            $table->string('voyage_number')->nullable();

            // Container details as JSON for flexibility
            $table->jsonb('container_details')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocean_shipments');
    }
};
