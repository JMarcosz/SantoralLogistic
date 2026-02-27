<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment methods: Cash, Check, Wire Transfer, Credit Card, etc.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique(); // CASH, CHECK, WIRE, CARD, etc.
            $table->string('type', 20)->default('cash'); // cash, bank, card, other
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default payment methods
        DB::table('payment_methods')->insert([
            ['name' => 'Efectivo', 'code' => 'CASH', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cheque', 'code' => 'CHECK', 'type' => 'bank', 'is_active' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transferencia Bancaria', 'code' => 'WIRE', 'type' => 'bank', 'is_active' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tarjeta de Crédito', 'code' => 'CARD', 'type' => 'card', 'is_active' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Depósito Bancario', 'code' => 'DEPOSIT', 'type' => 'bank', 'is_active' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
