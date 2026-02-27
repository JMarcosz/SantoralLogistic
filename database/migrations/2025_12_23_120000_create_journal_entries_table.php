<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Journal Entry Header
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('entry_number')->unique(); // e.g., JE-2025-000001
            $table->date('date')->index(); // Accounting date
            $table->string('description', 500); // General description

            // Status and Control
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft')->index();

            // Polymorphism: Where did this come from? (Invoice, Payment, etc.)
            $table->nullableMorphs('source');

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();

            // For reversals
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_of_entry_id')->nullable()->constrained('journal_entries');

            $table->timestamps();
        });

        // 2. Journal Entry Lines (Detail)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('restrict'); // Never delete account with balance!

            $table->string('description')->nullable(); // Line-specific memo

            // --- MULTI-CURRENCY SECTION ---

            // 1. Transaction Currency (e.g., USD)
            $table->char('currency_code', 3); // USD, DOP, EUR
            $table->decimal('exchange_rate', 15, 6)->default(1); // Exchange rate used

            // Amounts in original currency (what the invoice/bank says)
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);

            // 2. System Base Currency (e.g., DOP)
            // These are the fields you'll sum for financial reports
            $table->decimal('base_debit', 20, 4)->default(0);
            $table->decimal('base_credit', 20, 4)->default(0);

            $table->timestamps();

            // Indexes for optimizing reports
            $table->index(['account_id', 'journal_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
    }
};
