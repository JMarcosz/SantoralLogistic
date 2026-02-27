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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('code', 50);
            $table->enum('type', ['rack', 'floor', 'staging', 'dock'])->default('rack');
            $table->boolean('is_active')->default(true);
            $table->decimal('max_weight_kg', 10, 2)->nullable();
            $table->timestamps();

            // Unique code per warehouse
            $table->unique(['warehouse_id', 'code']);
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
