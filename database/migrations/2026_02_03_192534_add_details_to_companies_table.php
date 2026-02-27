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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->string('tax_id')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('tax_id');
            $table->boolean('is_active')->default(true)->after('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['name', 'tax_id', 'logo_path', 'is_active']);
        });
    }
};
