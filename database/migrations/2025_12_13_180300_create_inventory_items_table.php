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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('warehouse_receipt_id')->nullable()->constrained('warehouse_receipts')->nullOnDelete();

            $table->string('sku', 100);
            $table->text('description')->nullable();
            $table->decimal('qty', 12, 3)->default(0);
            $table->string('uom', 20)->default('PCS');
            $table->string('lot_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();

            $table->timestamps();

            // Indexes for fast lookups
            $table->index(['customer_id', 'sku']);
            $table->index(['warehouse_id', 'sku']);
            $table->index('location_id');
            $table->index('lot_number');
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
