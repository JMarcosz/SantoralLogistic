<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add reconciliation fields to journal_entry_lines.
     */
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->boolean('is_reconciled')->default(false)->after('base_credit');
            $table->foreignId('reconciled_by')->nullable()->after('is_reconciled')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable()->after('reconciled_by');

            $table->index('is_reconciled');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropIndex(['is_reconciled']);
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn(['is_reconciled', 'reconciled_by', 'reconciled_at']);
        });
    }
};
