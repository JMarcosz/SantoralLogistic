<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'is_default' => true,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'is_default' => false,
            ],
            [
                'code' => 'DOP',
                'name' => 'Dominican Peso',
                'symbol' => 'RD$',
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currencyData) {
            Currency::firstOrCreate(
                ['code' => $currencyData['code']],
                $currencyData
            );

            $this->command->info("Currency '{$currencyData['code']}' created or already exists.");
        }

        $this->command->info('Currency seeding completed successfully!');
    }
}
