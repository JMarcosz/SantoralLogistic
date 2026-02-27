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
        Schema::create('shipping_orders', function (Blueprint $table) {
            $table->id();

            // Order identification
            $table->string('order_number', 30)->unique();

            // Source quote (nullable - can create without quote)
            $table->foreignId('quote_id')
                ->nullable()
                ->constrained('quotes')
                ->nullOnDelete();

            // Customer
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->foreignId('contact_id')
                ->nullable()
                ->constrained('contacts')
                ->nullOnDelete();

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

            // Currency and totals
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete();
            $table->decimal('total_amount', 15, 4)->default(0);

            // Cargo details
            $table->integer('total_pieces')->nullable();
            $table->decimal('total_weight_kg', 10, 3)->nullable();
            $table->decimal('total_volume_cbm', 10, 3)->nullable();

            // Status
            $table->string('status', 30)->default('draft');

            // Dates
            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes
            $table->index('status');
            $table->index('customer_id');
            $table->index('quote_id');
            $table->index(['origin_port_id', 'destination_port_id'], 'so_lane_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_orders');
    }
};
