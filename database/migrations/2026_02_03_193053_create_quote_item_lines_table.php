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
        Schema::create('quote_item_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_item_id')->constrained('quote_items')->cascadeOnDelete();
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
        Schema::dropIfExists('quote_item_lines');
    }
};
