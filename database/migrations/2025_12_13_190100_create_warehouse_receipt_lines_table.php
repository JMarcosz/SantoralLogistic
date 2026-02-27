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
        Schema::create('warehouse_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_receipt_id')->constrained('warehouse_receipts')->cascadeOnDelete();
            $table->string('sku', 100);
            $table->text('description')->nullable();
            $table->decimal('expected_qty', 12, 3)->nullable();
            $table->decimal('received_qty', 12, 3)->default(0);
            $table->string('uom', 20)->default('PCS');
            $table->string('lot_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();

            $table->index('sku');
            $table->index('lot_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_receipt_lines');
    }
};
