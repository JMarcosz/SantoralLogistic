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
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('shipping_order_id')->constrained('shipping_orders')->cascadeOnDelete();
            $table->decimal('qty_reserved', 18, 4);
            $table->timestamps();

            // Indexes for efficient lookups
            $table->index('inventory_item_id', 'idx_reservations_inventory_item');
            $table->index('shipping_order_id', 'idx_reservations_shipping_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
