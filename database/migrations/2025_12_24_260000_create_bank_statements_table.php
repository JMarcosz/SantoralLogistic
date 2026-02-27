<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank statements - represents an imported bank statement for reconciliation.
     */
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();

            // Bank account from chart of accounts
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();

            // Statement period
            $table->date('statement_date');
            $table->date('period_start');
            $table->date('period_end');

            // Reference/description
            $table->string('reference', 100)->nullable(); // Statement number
            $table->text('description')->nullable();

            // Totals (denormalized for quick access)
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->decimal('closing_balance', 20, 4)->default(0);
            $table->decimal('total_debits', 20, 4)->default(0);
            $table->decimal('total_credits', 20, 4)->default(0);
            $table->integer('line_count')->default(0);
            $table->integer('reconciled_count')->default(0);

            // Status
            $table->enum('status', ['draft', 'in_progress', 'completed'])->default('draft');

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['account_id', 'statement_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
