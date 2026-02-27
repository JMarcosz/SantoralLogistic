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
        Schema::create('transport_modes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();       // AIR, OCEAN, GROUND
            $table->string('name', 100);                // Air, Ocean, Ground
            $table->text('description')->nullable();
            $table->boolean('supports_awb')->default(false);  // Air Waybill (Air)
            $table->boolean('supports_bl')->default(false);   // Bill of Lading (Ocean)
            $table->boolean('supports_pod')->default(true);   // Proof of Delivery
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_modes');
    }
};
