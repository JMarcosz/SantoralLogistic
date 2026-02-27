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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            // Quote identification
            $table->string('quote_number', 30)->unique();

            // Customer & Contact
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

            // Currency
            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnDelete();

            // Status
            $table->string('status', 20)->default('draft'); // QuoteStatus enum

            // Cargo details (for future per_kg/per_cbm calculations)
            $table->integer('total_pieces')->nullable();
            $table->decimal('total_weight_kg', 10, 3)->nullable();
            $table->decimal('total_volume_cbm', 10, 3)->nullable();
            $table->decimal('chargeable_weight_kg', 10, 3)->nullable();

            // Totals
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);

            // Validity & Notes
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();          // Internal notes
            $table->text('terms')->nullable();          // Terms & conditions

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
            $table->index(['origin_port_id', 'destination_port_id'], 'quotes_lane_idx');
            $table->index('valid_until');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
