<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add optional revenue account to charges for line-level accounting.
     * When set, invoice lines will post to this specific account.
     * When null, falls back to default revenue account in accounting settings.
     */
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->foreignId('revenue_account_id')
                ->nullable()
                ->after('sort_order')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropForeign(['revenue_account_id']);
            $table->dropColumn('revenue_account_id');
        });
    }
};
