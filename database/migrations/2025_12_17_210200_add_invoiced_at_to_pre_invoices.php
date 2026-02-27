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
        Schema::table('pre_invoices', function (Blueprint $table) {
            $table->timestamp('invoiced_at')->nullable()->after('export_reference')
                ->comment('Fecha y hora en que se convirtió a factura fiscal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_invoices', function (Blueprint $table) {
            $table->dropColumn('invoiced_at');
        });
    }
};
