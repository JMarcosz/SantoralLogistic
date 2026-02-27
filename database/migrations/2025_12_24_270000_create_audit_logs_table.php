<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit logs for tracking accounting operations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Actor (who performed the action)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 150)->nullable(); // Denormalized for history

            // Action type
            $table->string('action', 50); // created, updated, deleted, posted, reversed, etc.
            $table->string('module', 50); // journal_entries, settings, periods, payments, etc.

            // Target entity
            $table->string('entity_type', 100); // App\Models\JournalEntry, etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_label', 200)->nullable(); // Human-readable identifier

            // Change data
            $table->json('old_values')->nullable(); // State before change
            $table->json('new_values')->nullable(); // State after change
            $table->text('description')->nullable(); // Human-readable description

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
            $table->index('module');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
