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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // Relationship
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            // Contact info
            $table->string('name', 150);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('position', 100)->nullable();         // Job title/role

            // Classification
            $table->string('contact_type', 30)->nullable();      // general, billing, operations, sales
            $table->boolean('is_primary')->default(false);       // Primary contact marker

            // Additional
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();
            $table->softDeletesTz();

            // Indexes
            $table->index('customer_id');
            $table->index('email');
            $table->index('is_primary');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
