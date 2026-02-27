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
        Schema::create('shipping_order_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_order_id')
                ->constrained('shipping_orders')
                ->cascadeOnDelete();

            // Document metadata
            $table->string('type', 20);           // AWB, BL, CI, PL, OTHER
            $table->string('original_name');       // Original filename
            $table->string('path');               // Storage path
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // Size in bytes

            // Tracking
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestampsTz();

            // Indexes
            $table->index('type');
            $table->index(['shipping_order_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_order_documents');
    }
};
