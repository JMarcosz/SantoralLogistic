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
        Schema::create('warehouse_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('inventory_reservations')->nullOnDelete();
            $table->string('sku', 100);
            $table->text('description')->nullable();
            $table->decimal('qty_to_pick', 18, 4);
            $table->decimal('qty_picked', 18, 4)->default(0);
            $table->string('uom', 20);
            $table->string('location_code', 50)->nullable();
            $table->timestamps();

            $table->index('warehouse_order_id');
            $table->index('inventory_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_order_lines');
    }
};
