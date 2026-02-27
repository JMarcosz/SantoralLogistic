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
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete()->comment('ID de la factura');
            $table->foreignId('pre_invoice_line_id')->nullable()->constrained()->nullOnDelete()->comment('Línea de pre-factura origen');

            // Información de la línea
            $table->string('code', 50)->comment('Código del producto/servicio');
            $table->string('description')->comment('Descripción del producto/servicio');
            $table->decimal('qty', 18, 4)->comment('Cantidad');
            $table->decimal('unit_price', 18, 4)->comment('Precio unitario');
            $table->decimal('amount', 18, 4)->comment('Monto total de la línea');
            $table->decimal('tax_amount', 18, 4)->default(0)->comment('Monto de impuestos de la línea');
            $table->string('currency_code', 3)->comment('Código de moneda');
            $table->integer('sort_order')->nullable()->comment('Orden de visualización');

            $table->timestamps();

            // Índices
            $table->index('invoice_id');
            $table->index('pre_invoice_line_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
