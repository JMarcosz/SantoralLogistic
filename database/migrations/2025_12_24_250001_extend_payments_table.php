<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend payments table with new fields for multi-invoice and multi-party support.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Payment number (auto-generated)
            $table->string('payment_number', 30)->nullable()->unique()->after('id');

            // Type: inbound = customer payment (AR), outbound = supplier payment (AP)
            $table->string('type', 20)->default('inbound')->after('payment_number');

            // Direct customer relationship (for non-preinvoice payments)
            $table->foreignId('customer_id')->nullable()->after('type')->constrained()->nullOnDelete();

            // Supplier ID without constraint (table doesn't exist yet, will be added later)
            $table->unsignedBigInteger('supplier_id')->nullable()->after('customer_id');

            // Payment method as FK instead of string
            $table->foreignId('payment_method_id')->nullable()->after('supplier_id');

            // Exchange rate for multi-currency
            $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency_code');

            // Base amount (converted to base currency)
            $table->decimal('base_amount', 20, 4)->nullable()->after('amount');

            // Allocation tracking
            $table->decimal('amount_allocated', 20, 4)->default(0)->after('base_amount');
            $table->decimal('amount_unapplied', 20, 4)->default(0)->after('amount_allocated');

            // Bank account (for reconciliation)
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('notes');

            // Posted tracking
            $table->foreignId('posted_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable()->after('approved_at');

            // Indexes
            $table->index(['type', 'status']);
            $table->index('customer_id');
        });

        // Add foreign key constraint for payment_method after column creation
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->restrictOnDelete();
            $table->foreign('bank_account_id')->references('id')->on('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['posted_by']);
            $table->dropForeign(['customer_id']);

            $table->dropIndex(['type', 'status']);
            $table->dropIndex(['customer_id']);

            $table->dropColumn([
                'payment_number',
                'type',
                'customer_id',
                'supplier_id',
                'payment_method_id',
                'exchange_rate',
                'base_amount',
                'amount_allocated',
                'amount_unapplied',
                'bank_account_id',
                'posted_by',
                'posted_at',
            ]);
        });
    }
};
