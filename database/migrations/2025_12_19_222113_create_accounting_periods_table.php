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
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();

            // Period identification
            $table->unsignedSmallInteger('year')->comment('Fiscal year (e.g., 2025)');
            $table->unsignedTinyInteger('month')->comment('Month (1-12)');

            // Status
            $table->enum('status', ['open', 'closed'])
                ->default('open')
                ->comment('Period status: open = allow postings, closed = locked');

            // Lock date (optional soft lock before full close)
            $table->date('lock_date')->nullable()
                ->comment('Optional: prevent postings before this date even if period open');

            // Audit trail
            $table->timestamp('closed_at')->nullable()
                ->comment('When period was closed');
            $table->foreignId('closed_by')->nullable()->constrained('users')
                ->comment('User who closed the period');

            $table->timestamp('reopened_at')->nullable()
                ->comment('When period was last reopened');
            $table->foreignId('reopened_by')->nullable()->constrained('users')
                ->comment('User who reopened the period');

            $table->timestamps();

            // Indexes
            $table->unique(['year', 'month']); // One period per year-month
            $table->index('status');
            $table->index(['year', 'month', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
