<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('sales_rep_id')->nullable()->after('created_by')->constrained('users');
        });

        // Backfill sales_rep_id with created_by
        DB::statement('UPDATE quotes SET sales_rep_id = created_by');

        Schema::table('quote_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('quote_lines', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 4)->nullable()->after('unit_price');
            } else {
                $table->decimal('unit_cost', 12, 4)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_lines', function (Blueprint $table) {
            // Revert data to valid state before constraints
            DB::statement('UPDATE quote_lines SET unit_cost = 0 WHERE unit_cost IS NULL');
            $table->decimal('unit_cost', 12, 4)->default(0)->nullable(false)->change();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['sales_rep_id']);
            $table->dropColumn('sales_rep_id');
        });
    }
};
