<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make pre_invoice_id nullable on payments table.
 * 
 * Payments can be made directly to invoices without a pre-invoice,
 * so this column must be nullable.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign key first (if exists)
            if (Schema::hasColumn('payments', 'pre_invoice_id')) {
                // For SQLite we need to recreate, for PostgreSQL we can alter
                // Using doctrine's approach for cross-database compatibility
                $table->foreignId('pre_invoice_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Cannot convert back to NOT NULL if there are null values
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('pre_invoice_id')->nullable(false)->change();
        });
    }
};
