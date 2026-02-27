<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add withholding tax fields to payments table.
 * 
 * These fields support ISR (Impuesto Sobre la Renta) and ITBIS (ITBIS Retenido)
 * withholding taxes that may be applied by customers when paying invoices.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // ISR Withholding (Retención ISR) - typically 10% or 27%
            $table->decimal('isr_withholding_amount', 18, 4)->default(0)->after('base_amount');

            // ITBIS Withholding (Retención ITBIS) - typically 30% or 100% of ITBIS
            $table->decimal('itbis_withholding_amount', 18, 4)->default(0)->after('isr_withholding_amount');

            // Net amount received (amount - withholdings)
            $table->decimal('net_amount', 18, 4)->nullable()->after('itbis_withholding_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'isr_withholding_amount',
                'itbis_withholding_amount',
                'net_amount',
            ]);
        });
    }
};
