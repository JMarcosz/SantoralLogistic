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
        Schema::table('pre_invoice_lines', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(true)->after('tax_amount');
            $table->decimal('tax_rate', 5, 2)->default(0.18)->after('is_taxable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_invoice_lines', function (Blueprint $table) {
            $table->dropColumn(['is_taxable', 'tax_rate']);
        });
    }
};
