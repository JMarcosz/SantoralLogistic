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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique()->comment('Número interno de factura: INV-YYYY-NNNNNN');
            $table->string('ncf', 19)->unique()->comment('Comprobante fiscal DGII');
            $table->string('ncf_type', 4)->comment('Tipo de NCF: B01, B02, B14, etc.');

            // Relaciones
            $table->foreignId('customer_id')->constrained()->comment('Cliente facturado');
            $table->foreignId('pre_invoice_id')->nullable()->constrained()->nullOnDelete()->comment('Pre-factura origen');
            $table->foreignId('shipping_order_id')->nullable()->constrained()->nullOnDelete()->comment('Orden de envío origen');

            // Información básica
            $table->string('currency_code', 3)->comment('Código de moneda');
            $table->date('issue_date')->comment('Fecha de emisión');
            $table->date('due_date')->nullable()->comment('Fecha de vencimiento');
            $table->string('status', 50)->default('issued')->comment('Estado: issued, cancelled');

            // Montos para DGII
            $table->decimal('subtotal_amount', 18, 4)->default(0)->comment('Subtotal sin impuestos');
            $table->decimal('tax_amount', 18, 4)->default(0)->comment('Monto de impuestos');
            $table->decimal('total_amount', 18, 4)->default(0)->comment('Total de la factura');
            $table->decimal('exempt_amount', 18, 4)->default(0)->comment('Monto exento para 607');
            $table->decimal('taxable_amount', 18, 4)->default(0)->comment('Base imponible');

            // Notas y observaciones
            $table->text('notes')->nullable();

            // Cancelación
            $table->timestamp('cancelled_at')->nullable()->comment('Fecha y hora de cancelación');
            $table->string('cancellation_reason')->nullable()->comment('Motivo de cancelación');

            $table->timestamps();

            // Índices para performance
            $table->index('customer_id');
            $table->index('pre_invoice_id');
            $table->index('shipping_order_id');
            $table->index('issue_date');
            $table->index('status');
            $table->index('ncf_type');
            $table->index(['customer_id', 'issue_date'], 'idx_invoices_customer_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
