<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounting Settings - Singleton table for default GL accounts.
     * These accounts are used for automatic journal posting from invoices, payments, etc.
     */
    public function up(): void
    {
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();

            // Accounts Receivable & Payable
            $table->foreignId('ar_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Default Accounts Receivable');
            $table->foreignId('ap_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Default Accounts Payable');

            // Revenue & Expenses
            $table->foreignId('revenue_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Default Revenue/Sales Account');
            $table->foreignId('cogs_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Cost of Goods Sold');
            $table->foreignId('discount_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Sales Discounts Given');

            // Inventory
            $table->foreignId('inventory_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Inventory Asset Account');

            // Cash & Bank
            $table->foreignId('cash_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Cash on Hand');
            $table->foreignId('bank_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Default Bank Account');

            // Exchange Differences
            $table->foreignId('exchange_gain_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Foreign Exchange Gain');
            $table->foreignId('exchange_loss_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('Foreign Exchange Loss');

            // Retentions (DGII)
            $table->foreignId('isr_retention_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('ISR Retention Payable');
            $table->foreignId('itbis_retention_account_id')
                ->nullable()
                ->constrained('accounts')
                ->comment('ITBIS Retention Payable');

            $table->timestamps();
        });

        // Seed initial empty record
        DB::table('accounting_settings')->insert([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_settings');
    }
};
