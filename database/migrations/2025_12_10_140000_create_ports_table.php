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
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();           // Internal code (UN/LOCODE preferred)
            $table->string('name', 150);                     // Port/airport/city name
            $table->string('country', 100);                  // Country name or ISO code
            $table->string('city', 100)->nullable();         // City name
            $table->string('unlocode', 10)->nullable();      // Full UN/LOCODE (e.g., "USMIA")
            $table->string('iata_code', 5)->nullable();      // IATA code for airports
            $table->enum('type', ['air', 'ocean', 'ground']); // Location type
            $table->string('timezone', 50)->nullable();      // Timezone identifier
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes for faster lookups
            $table->index('unlocode');
            $table->index('iata_code');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ports');
    }
};
