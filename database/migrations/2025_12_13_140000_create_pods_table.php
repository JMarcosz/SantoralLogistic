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
        Schema::create('pods', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship to PickupOrder or DeliveryOrder
            $table->morphs('podable');
            
            // Timestamp when the POD was captured
            $table->timestamp('happened_at');
            
            // Optional geolocation
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Optional signature/photo evidence
            $table->string('image_path', 255)->nullable();
            
            // Optional notes
            $table->text('notes')->nullable();
            
            // User who registered the POD
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Ensure one POD per order
            $table->unique(['podable_type', 'podable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pods');
    }
};
