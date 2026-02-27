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
        Schema::table('shipping_orders', function (Blueprint $table) {
            // Footer terms reference and snapshot
            $table->foreignId('footer_terms_id')
                ->nullable()
                ->after('notes')
                ->constrained('terms')
                ->nullOnDelete();

            $table->text('footer_terms_snapshot')
                ->nullable()
                ->after('footer_terms_id');

            // Note: Payment terms for shipping orders can be added later
            // if business requirements demand it. For now, we focus on
            // footer/legal terms only.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('footer_terms_id');
            $table->dropColumn('footer_terms_snapshot');
        });
    }
};
