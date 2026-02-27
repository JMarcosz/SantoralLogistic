<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tax Mappings - Maps tax types (ITBIS, ISC, etc.) to GL accounts.
     * Used for automatic journal posting of tax amounts.
     */
    public function up(): void
    {
        Schema::create('tax_mappings', function (Blueprint $table) {
            $table->id();

            // Tax identification
            $table->string('code', 20)->unique(); // ITBIS, ISC, ITBIS_RETENIDO
            $table->string('name'); // Descriptive name
            $table->text('description')->nullable();

            // Tax rate
            $table->decimal('rate', 5, 2); // 18.00 for ITBIS

            // GL Accounts
            $table->foreignId('sales_account_id')
                ->constrained('accounts')
                ->comment('Tax Payable - for sales');
            $table->foreignId('purchase_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Tax Receivable - for purchases');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_mappings');
    }
};
