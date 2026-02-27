<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $usd = Currency::where('code', 'USD')->first();
        $usdId = $usd?->id;

        $customers = [
            [
                'code' => 'CUST001',
                'name' => 'Importadora Global S.R.L.',
                'tax_id' => '101234567',
                'billing_address' => 'Av. Winston Churchill #123, Torre Empresarial, Piso 5',
                'city' => 'Santo Domingo',
                'state' => 'Distrito Nacional',
                'country' => 'República Dominicana',
                'email_billing' => 'facturacion@importadoraglobal.com',
                'phone' => '+1 809-555-0100',
                'status' => 'active',
                'credit_limit' => 50000.00,
                'currency_id' => $usdId,
                'payment_terms' => '30 días',
            ],
            [
                'code' => 'CUST002',
                'name' => 'Caribbean Trade LLC',
                'tax_id' => 'FL-123456',
                'billing_address' => '2500 NW 87th Ave, Suite 200',
                'city' => 'Miami',
                'state' => 'Florida',
                'country' => 'United States',
                'email_billing' => 'accounts@caribbeantrade.com',
                'phone' => '+1 305-555-0200',
                'status' => 'active',
                'credit_limit' => 100000.00,
                'currency_id' => $usdId,
                'payment_terms' => 'Net 45',
            ],
            [
                'code' => 'CUST003',
                'name' => 'Logistics Express Dominicana',
                'tax_id' => '102345678',
                'billing_address' => 'Calle El Sol #45',
                'city' => 'Santiago',
                'state' => 'Santiago',
                'country' => 'República Dominicana',
                'email_billing' => 'pagos@logisticsexpress.do',
                'phone' => '+1 809-555-0300',
                'status' => 'active',
                'credit_limit' => 25000.00,
                'currency_id' => $usdId,
                'payment_terms' => '15 días',
            ],
            [
                'code' => 'CUST004',
                'name' => 'Distribuidora del Norte',
                'tax_id' => '103456789',
                'billing_address' => 'Autopista Duarte Km 12',
                'city' => 'La Vega',
                'state' => 'La Vega',
                'country' => 'República Dominicana',
                'email_billing' => 'contabilidad@distnorte.com',
                'phone' => '+1 809-555-0400',
                'status' => 'prospect',
                'credit_limit' => null,
                'currency_id' => $usdId,
                'payment_terms' => null,
            ],
            [
                'code' => 'CUST005',
                'name' => 'Atlantic Shipping Corp',
                'tax_id' => 'NY-789012',
                'billing_address' => '350 5th Avenue, Floor 10',
                'city' => 'New York',
                'state' => 'New York',
                'country' => 'United States',
                'email_billing' => 'billing@atlanticshipping.com',
                'phone' => '+1 212-555-0500',
                'status' => 'active',
                'credit_limit' => 200000.00,
                'currency_id' => $usdId,
                'payment_terms' => 'Net 60',
            ],
        ];

        foreach ($customers as $data) {
            Customer::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );

            $this->command->info("Customer '{$data['code']}' - {$data['name']} created or exists.");
        }

        $this->command->info('Customer seeding completed!');
    }
}
