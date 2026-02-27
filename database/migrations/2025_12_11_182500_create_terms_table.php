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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('code', 50);           // E.g. 'NET30', 'QUOTE_STD', 'SO_STD'
            $table->string('name', 255);          // Human-readable name
            $table->text('description')->nullable(); // Internal admin notes

            // Content
            $table->text('body');                 // The actual text to display in documents

            // Classification
            $table->string('type', 50);           // 'payment', 'quote_footer', 'shipping_order_footer', 'invoice_footer'
            $table->string('scope', 50)->nullable(); // Future: 'all', 'air', 'ocean', 'ground'

            // Flags
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->unique(['type', 'code']);

            // NOTE: Future extensions planned:
            // - customer_id: For client-specific terms
            // - country_code: For regional legal requirements
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
