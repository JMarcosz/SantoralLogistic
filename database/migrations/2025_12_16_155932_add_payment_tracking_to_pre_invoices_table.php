<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pre_invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 18, 4)->default(0)->after('total_amount');
            $table->decimal('balance', 18, 4)->default(0)->after('paid_amount');
            $table->timestamp('paid_at')->nullable()->after('balance');
        });

        // Update existing records to set balance = total_amount where status is not cancelled
        DB::statement("UPDATE pre_invoices SET balance = total_amount WHERE status != 'cancelled'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_invoices', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'balance', 'paid_at']);
        });
    }
};
