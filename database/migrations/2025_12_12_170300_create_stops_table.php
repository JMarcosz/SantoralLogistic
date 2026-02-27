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
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->morphs('stoppable'); // Creates stoppable_type and stoppable_id
            $table->integer('sequence')->default(1);
            $table->string('name');
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->dateTime('window_start')->nullable();
            $table->dateTime('window_end')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Composite index for efficient querying
            $table->index(['stoppable_type', 'stoppable_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
