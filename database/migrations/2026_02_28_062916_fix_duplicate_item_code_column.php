<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            
            if (!Schema::hasColumn('inventory_items', 'item_code')) {
                $table->string('item_code', 100)
                      ->nullable()
                      ->after('warehouse_receipt_id');
            }

        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            
            if (Schema::hasColumn('inventory_items', 'item_code')) {
                $table->dropColumn('item_code');
            }

        });
    }
};