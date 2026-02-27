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
        Schema::create('shipping_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_order_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('container'); // container, vehicle, loose_cargo
            $table->string('identifier')->nullable(); // Container Number, VIN, etc.
            $table->string('seal_number')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_order_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_order_item_id')->constrained('shipping_order_items')->cascadeOnDelete();
            $table->integer('pieces');
            $table->text('description');
            $table->decimal('weight_kg', 12, 3);
            $table->decimal('volume_cbm', 12, 3);
            $table->string('marks_numbers')->nullable();
            $table->string('hs_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_order_item_lines');
        Schema::dropIfExists('shipping_order_items');
    }
};
