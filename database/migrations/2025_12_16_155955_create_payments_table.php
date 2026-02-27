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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 4);
            $table->string('currency_code', 3);
            $table->date('payment_date');
            $table->string('payment_method', 50)->nullable(); // cash, check, transfer, card
            $table->string('reference', 100)->nullable();  // check #, transfer ref
            $table->text('notes')->nullable();

            // Approval workflow
            $table->string('status', 20)->default('pending'); // pending, approved, voided
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();

            $table->timestamps();

            $table->index(['pre_invoice_id', 'status']);
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
