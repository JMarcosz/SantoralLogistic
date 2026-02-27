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
        Schema::create('products_services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('type', 30);                          // service, product, fee
            $table->string('uom', 30)->nullable();               // kg, cbm, shipment, unit, etc.
            $table->foreignId('default_currency_id')
                ->nullable()
                ->constrained('currencies')
                ->nullOnDelete();
            $table->decimal('default_unit_price', 15, 4)->nullable();
            $table->boolean('taxable')->default(true);
            $table->string('gl_account_code', 50)->nullable();   // For future accounting integration
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes
            $table->index('type');
            $table->index('is_active');
            $table->index('taxable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_services');
    }
};
