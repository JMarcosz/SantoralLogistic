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
        Schema::create('rates', function (Blueprint $table) {
            $table->id();

            // Lane definition
            $table->foreignId('origin_port_id')
                ->constrained('ports')
                ->cascadeOnDelete();
            $table->foreignId('destination_port_id')
                ->constrained('ports')
                ->cascadeOnDelete();
            $table->foreignId('transport_mode_id')
                ->constrained('transport_modes')
                ->cascadeOnDelete();
            $table->foreignId('service_type_id')
                ->constrained('service_types')
                ->cascadeOnDelete();

            // Pricing
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete();
            $table->string('charge_basis', 30)->default('per_shipment'); // per_kg, per_cbm, per_shipment
            $table->decimal('base_amount', 15, 4);
            $table->decimal('min_amount', 15, 4)->nullable();

            // Validity
            $table->date('valid_from');
            $table->date('valid_to')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes for querying
            $table->index(['origin_port_id', 'destination_port_id'], 'rates_lane_idx');
            $table->index(['transport_mode_id', 'service_type_id'], 'rates_mode_service_idx');
            $table->index(['valid_from', 'valid_to'], 'rates_validity_idx');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
