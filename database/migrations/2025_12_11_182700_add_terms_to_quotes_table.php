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
            // Payment terms reference and snapshot
            $table->foreignId('payment_terms_id')
                ->nullable()
                ->after('terms')
                ->constrained('terms')
                ->nullOnDelete();

            $table->text('payment_terms_snapshot')
                ->nullable()
                ->after('payment_terms_id');

            // Footer terms reference and snapshot
            $table->foreignId('footer_terms_id')
                ->nullable()
                ->after('payment_terms_snapshot')
                ->constrained('terms')
                ->nullOnDelete();

            $table->text('footer_terms_snapshot')
                ->nullable()
                ->after('footer_terms_id');

            // Note: The existing 'terms' column is kept for backward compatibility
            // but should be considered deprecated. New code should use
            // payment_terms_snapshot and footer_terms_snapshot instead.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_terms_id');
            $table->dropColumn('payment_terms_snapshot');
            $table->dropConstrainedForeignId('footer_terms_id');
            $table->dropColumn('footer_terms_snapshot');
        });
    }
};
