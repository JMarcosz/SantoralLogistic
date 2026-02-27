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
        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();

            // Parent quote
            $table->foreignId('quote_id')
                ->constrained('quotes')
                ->cascadeOnDelete();

            // Product/Service
            $table->foreignId('product_service_id')
                ->constrained('products_services')
                ->cascadeOnDelete();

            // Line details
            $table->string('description', 500)->nullable(); // Override product description
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4);

            // Discount and Tax
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0); // Snapshot from product

            // Calculated total
            $table->decimal('line_total', 15, 4);

            // Ordering
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestampsTz();

            // Indexes
            $table->index('quote_id');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_lines');
    }
};
