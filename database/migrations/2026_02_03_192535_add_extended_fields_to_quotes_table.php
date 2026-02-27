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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('division')->nullable()->after('quote_number');
            $table->integer('transit_days')->nullable()->after('valid_until');
            $table->string('incoterms')->nullable()->after('transit_days');

            // Foreign Keys
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete()->after('division');
            $table->foreignId('issuing_company_id')->nullable()->constrained('companies')->nullOnDelete()->after('project_id');
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete()->after('transport_mode_id');
            $table->foreignId('shipper_id')->nullable()->constrained('customers')->nullOnDelete()->after('customer_id');
            $table->foreignId('consignee_id')->nullable()->constrained('contacts')->nullOnDelete()->after('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['issuing_company_id']);
            $table->dropForeign(['carrier_id']);
            $table->dropForeign(['shipper_id']);
            $table->dropForeign(['consignee_id']);

            $table->dropColumn([
                'division',
                'transit_days',
                'incoterms',
                'project_id',
                'issuing_company_id',
                'carrier_id',
                'shipper_id',
                'consignee_id'
            ]);
        });
    }
};
