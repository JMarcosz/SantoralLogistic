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
        Schema::create('dgii_export_logs', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 10); // '607' or '608'
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('record_count')->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->timestamps();

            // Indexes
            $table->index(['report_type', 'period_start']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dgii_export_logs');
    }
};
