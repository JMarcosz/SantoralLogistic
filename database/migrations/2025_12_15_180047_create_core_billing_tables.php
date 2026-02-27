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
        // 1. Taxes
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name');
            $table->decimal('rate_percent', 5, 2);
            $table->string('country', 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Charges
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50);
            $table->string('description');
            $table->string('charge_type', 50); // freight, surcharge, tax, other
            $table->string('basis', 50); // flat, per_kg, per_cbm, per_shipment
            $table->string('currency_code', 3);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('qty', 18, 4)->default(1);
            $table->decimal('amount', 18, 4);
            $table->boolean('is_tax_included')->default(false);
            $table->boolean('is_manual')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();

            $table->index(['shipping_order_id', 'code']);
            $table->index(['quote_id', 'code']);
        });

        // 3. Charge Tax Pivot
        Schema::create('charge_tax', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained()->cascadeOnDelete();
            $table->decimal('tax_amount', 18, 4);
            $table->timestamps();
        });

        // 4. Pre Invoices
        Schema::create('pre_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('shipping_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency_code', 3);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('status', 50); // draft, issued, cancelled
            $table->decimal('subtotal_amount', 18, 4)->default(0);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->decimal('total_amount', 18, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('external_ref', 100)->nullable();
            $table->timestamps();
        });

        // 5. Pre Invoice Lines
        Schema::create('pre_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50);
            $table->string('description');
            $table->decimal('qty', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('amount', 18, 4);
            $table->decimal('tax_amount', 18, 4)->default(0);
            $table->string('currency_code', 3);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        // 6. Currency Rates
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3);
            $table->string('quote_currency', 3);
            $table->decimal('rate', 18, 8);
            $table->date('rate_date');
            $table->timestamps();

            $table->unique(['base_currency', 'quote_currency', 'rate_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
        Schema::dropIfExists('pre_invoice_lines');
        Schema::dropIfExists('pre_invoices');
        Schema::dropIfExists('charge_tax');
        Schema::dropIfExists('charges');
        Schema::dropIfExists('taxes');
    }
};
