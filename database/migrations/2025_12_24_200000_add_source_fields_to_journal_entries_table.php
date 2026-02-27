<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add unique constraint on source fields for idempotency.
     * The source_type and source_id columns already exist from nullableMorphs('source').
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // Unique constraint for idempotency - prevents duplicate entries
            // source fields already exist from nullableMorphs('source')
            $table->unique(['source_type', 'source_id'], 'journal_entries_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropUnique('journal_entries_source_unique');
        });
    }
};
