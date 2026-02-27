<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add payment tracking fields to invoices table.
     * - amount_paid: cumulative amount paid
     * - balance: remaining amount to be paid
     * - payment_status: pending, partial, paid
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Amount already paid against this invoice
            $table->decimal('amount_paid', 18, 4)->default(0)->after('total_amount');
            
            // Remaining balance (total_amount - amount_paid)
            $table->decimal('balance', 18, 4)->default(0)->after('amount_paid');
            
            // Payment status: pending (no payments), partial (some paid), paid (fully paid)
            $table->string('payment_status', 20)->default('pending')->after('status');
            
            // Index for filtering by payment status
            $table->index('payment_status');
        });

        // Initialize balance = total_amount for existing invoices
        DB::statement('UPDATE invoices SET balance = total_amount WHERE balance = 0');

        // Recalculate from existing payment allocations
        DB::statement("
            UPDATE invoices 
            SET amount_paid = COALESCE((
                SELECT SUM(pa.amount_applied)
                FROM payment_allocations pa
                INNER JOIN payments p ON pa.payment_id = p.id
                WHERE pa.invoice_id = invoices.id
                AND p.status NOT IN ('voided', 'draft')
            ), 0)
        ");

        // Update balance based on amount_paid
        DB::statement('UPDATE invoices SET balance = total_amount - amount_paid');

        // Update payment_status based on balance
        DB::statement("UPDATE invoices SET payment_status = 'paid' WHERE balance <= 0 AND amount_paid > 0");
        DB::statement("UPDATE invoices SET payment_status = 'partial' WHERE balance > 0 AND amount_paid > 0");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['amount_paid', 'balance', 'payment_status']);
        });
    }
};
