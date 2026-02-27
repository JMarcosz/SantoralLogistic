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
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['created_by', 'deleted_by']);
            $table->dropSoftDeletes();
        });
    }
};
