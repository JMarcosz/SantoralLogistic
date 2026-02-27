<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily account balances - summary table for fast ledger queries.
     * 
     * Updated when journal entries are posted/reversed.
     * Stores daily totals per account to avoid recalculating from millions of lines.
     */
    public function up(): void
    {
        Schema::create('daily_balances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->date('date')->index();

            // Totals for the day (in base currency)
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);

            // Running balance at end of day
            // For debit-normal accounts: balance = sum(debits) - sum(credits)
            // For credit-normal accounts: balance = sum(credits) - sum(debits)
            $table->decimal('balance', 20, 4)->default(0);

            // Entry count for the day (for debugging/verification)
            $table->unsignedInteger('entry_count')->default(0);

            $table->timestamps();

            // Unique constraint: one record per account per day
            $table->unique(['account_id', 'date']);

            // Index for range queries
            $table->index(['account_id', 'date', 'balance']);
        });

        // Add composite indexes to optimize ledger queries
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            // For joining with journal_entries filtered by date/status
            $table->index(['account_id', 'created_at'], 'jel_account_created_idx');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            // For filtering by date range and status
            $table->index(['date', 'status'], 'je_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('je_date_status_idx');
        });

        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropIndex('jel_account_created_idx');
        });

        Schema::dropIfExists('daily_balances');
    }
};
