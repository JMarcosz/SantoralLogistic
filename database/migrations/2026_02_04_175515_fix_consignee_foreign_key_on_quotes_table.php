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
            // Drop the incorrect foreign key referencing contacts
            $table->dropForeign(['consignee_id']);

            // Add the correct foreign key referencing customers
            $table->foreign('consignee_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Revert back to contacts if needed
            $table->dropForeign(['consignee_id']);

            $table->foreign('consignee_id')
                ->references('id')
                ->on('contacts')
                ->nullOnDelete();
        });
    }
};
