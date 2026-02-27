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
        Schema::table('quotes', function (Blueprint $table) {
            // Drop the incorrect foreign key referencing companies
            $table->dropForeign(['issuing_company_id']);

            // Add the correct foreign key referencing company_settings
            $table->foreign('issuing_company_id')
                ->references('id')
                ->on('company_settings')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['issuing_company_id']);

            $table->foreign('issuing_company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }
};
