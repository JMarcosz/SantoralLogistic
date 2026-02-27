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
        Schema::create('customer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('description');
            $table->string('default_uom')->nullable()->default('PCS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['customer_id', 'code']);
            $table->index(['customer_id', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_items');
    }
};
