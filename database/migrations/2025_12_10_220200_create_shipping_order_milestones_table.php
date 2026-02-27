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
        Schema::create('shipping_order_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_order_id')
                ->constrained('shipping_orders')
                ->cascadeOnDelete();

            // Milestone identification
            $table->string('code', 50);           // e.g., "BOOKED", "DEPARTED_ORIGIN"
            $table->string('label');              // e.g., "Reservado", "Salida de origen"
            $table->string('status', 50)->nullable(); // Optional grouping status

            // Event details
            $table->dateTimeTz('happened_at');    // When the milestone occurred
            $table->string('location')->nullable(); // City/port/warehouse
            $table->text('remarks')->nullable();   // Additional notes

            // Tracking
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampsTz();

            // Indexes
            $table->index('code');
            $table->index('happened_at');
            $table->index(['shipping_order_id', 'happened_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_order_milestones');
    }
};
