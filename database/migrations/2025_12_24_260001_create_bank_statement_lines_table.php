<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank statement lines - individual transactions from bank statement.
     */
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_statement_id')->constrained()->cascadeOnDelete();

            // Transaction details
            $table->date('transaction_date');
            $table->date('value_date')->nullable(); // Fecha valor
            $table->string('reference', 100)->nullable(); // Check #, transfer ref
            $table->text('description')->nullable();

            // Amount (positive = deposit/credit, negative = withdrawal/debit)
            $table->decimal('amount', 20, 4);
            $table->decimal('running_balance', 20, 4)->nullable();

            // Transaction type hints
            $table->string('transaction_type', 50)->nullable(); // DEP, CHK, TRF, etc.

            // Reconciliation
            $table->foreignId('journal_entry_line_id')->nullable()
                ->constrained('journal_entry_lines')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()
                ->constrained('payments')->nullOnDelete();
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->text('reconciliation_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('transaction_date');
            $table->index('is_reconciled');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
