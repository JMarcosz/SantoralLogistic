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
        Schema::create('fiscal_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('ncf_type', 4)->comment('Tipo de NCF: B01, B02, B14, etc.');
            $table->string('series', 10)->nullable()->comment('Serie opcional: sucursal/serie interna');
            $table->string('ncf_from', 19)->comment('NCF inicial del rango (formato completo)');
            $table->string('ncf_to', 19)->comment('NCF final del rango');
            $table->string('current_ncf', 19)->nullable()->comment('Último NCF emitido en este rango');
            $table->date('valid_from')->comment('Fecha de inicio de vigencia del rango');
            $table->date('valid_to')->comment('Fecha de fin de vigencia del rango');
            $table->boolean('is_active')->default(true)->comment('Si el rango está activo');
            $table->timestamps();

            // Índices para búsquedas eficientes
            $table->index(['ncf_type', 'series', 'is_active'], 'idx_fiscal_seq_type_series_active');
            $table->index(['valid_from', 'valid_to'], 'idx_fiscal_seq_validity');
            $table->index('is_active');

            // Constraint única para prevenir rangos completamente duplicados
            // NOTA: Esto NO previene rangos solapados, solo duplicados exactos
            // La validación de solapamiento se hace a nivel de dominio
            $table->unique(['ncf_type', 'series', 'ncf_from', 'ncf_to'], 'uq_fiscal_seq_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_sequences');
    }
};
