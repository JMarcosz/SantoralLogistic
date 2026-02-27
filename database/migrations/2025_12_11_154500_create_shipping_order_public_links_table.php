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
        Schema::create('shipping_order_public_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_order_id')
                ->constrained('shipping_orders')
                ->cascadeOnDelete();

            // Secure unique token for public access
            $table->string('token', 64)->unique();

            // Whether the link is currently active
            $table->boolean('is_active')->default(true);

            // Optional expiration date
            $table->timestampTz('expires_at')->nullable();

            $table->timestampsTz();

            // Index for faster lookups
            $table->index(['token', 'is_active']);
            $table->index('shipping_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_order_public_links');
    }
};
