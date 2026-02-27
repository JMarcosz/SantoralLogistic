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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('code', 20)->unique()->comment('Account code (e.g., 1000, 1200)');
            $table->string('name')->comment('Account name');

            // Classification
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense'])
                ->comment('Account type for financial statements');
            $table->enum('normal_balance', ['debit', 'credit'])
                ->comment('Normal balance side (D or C)');

            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete()
                ->comment('Parent account for tree structure');
            $table->unsignedTinyInteger('level')->default(1)
                ->comment('Tree depth level (1=top, 2=child, etc.)');

            // Posting rules
            $table->boolean('is_postable')->default(true)
                ->comment('Can this account have journal entries? (false = header/group)');
            $table->boolean('requires_subsidiary')->default(false)
                ->comment('Requires customer/vendor link (e.g., AR, AP)');

            // Status
            $table->boolean('is_active')->default(true)
                ->comment('Active accounts visible in dropdowns');

            // Multi-currency (optional - for future)
            $table->string('currency_code', 3)->nullable()
                ->comment('If set, account is currency-specific (NULL = base currency)');

            // Metadata
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('parent_id');
            $table->index('type');
            $table->index(['is_active', 'is_postable']);
            $table->index('code'); // Already unique, but helpful for searches
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
