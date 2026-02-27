<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment allocations - links payments to invoices.
     * Allows partial payments and multi-invoice payments.
     */
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();

            // Amount applied from this payment to this invoice
            $table->decimal('amount_applied', 20, 4);

            // If payment currency differs from invoice currency
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('base_amount_applied', 20, 4)->nullable();

            $table->timestamps();

            // Prevent duplicate allocations
            $table->unique(['payment_id', 'invoice_id']);

            // Index for finding payments by invoice
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
