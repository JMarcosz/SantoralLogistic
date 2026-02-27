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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('code', 30)->unique()->nullable();  // Internal customer code
            $table->string('name', 200);                       // Legal or trade name
            $table->string('tax_id', 50)->nullable();          // RNC / VAT / NIF
            $table->string('tax_id_type', 20)->nullable();     // RNC, CEDULA, OTHER
            $table->string('fiscal_name', 255)->nullable();    // Fiscal name for invoicing
            $table->string('ncf_type_default', 3)->nullable(); // B01, B02, B14, B15
            $table->string('series', 1)->nullable();           // NCF series (A-Z)

            // Addresses
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();

            // Contact
            $table->string('email_billing', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website', 255)->nullable();

            // Status and Classification
            $table->string('status', 20)->default('prospect'); // prospect, active, inactive

            // Financial
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->foreignId('currency_id')
                ->nullable()
                ->constrained('currencies')
                ->nullOnDelete();
            $table->string('payment_terms', 100)->nullable();  // "30 days", "COD", etc.

            // Additional
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes
            $table->index('status');
            $table->index('country');
            $table->index('is_active');
            $table->index('tax_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
