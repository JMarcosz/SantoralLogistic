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
        Schema::table('company_settings', function (Blueprint $table) {
            // Default terms references
            $table->foreignId('default_payment_terms_id')
                ->nullable()
                ->after('is_active')
                ->constrained('terms')
                ->nullOnDelete();

            $table->foreignId('default_quote_terms_id')
                ->nullable()
                ->after('default_payment_terms_id')
                ->constrained('terms')
                ->nullOnDelete();

            $table->foreignId('default_so_terms_id')
                ->nullable()
                ->after('default_quote_terms_id')
                ->constrained('terms')
                ->nullOnDelete();

            $table->foreignId('default_invoice_terms_id')
                ->nullable()
                ->after('default_so_terms_id')
                ->constrained('terms')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_payment_terms_id');
            $table->dropConstrainedForeignId('default_quote_terms_id');
            $table->dropConstrainedForeignId('default_so_terms_id');
            $table->dropConstrainedForeignId('default_invoice_terms_id');
        });
    }
};
