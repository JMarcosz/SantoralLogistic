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
            $table->timestamp('exported_at')->nullable()->after('external_ref');
            $table->string('export_reference', 100)->nullable()->after('exported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_invoices', function (Blueprint $table) {
            $table->dropColumn(['exported_at', 'export_reference']);
        });
    }
};
