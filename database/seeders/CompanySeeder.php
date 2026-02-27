<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = CompanySetting::firstOrCreate(
            ['rnc' => '130000001'],
            [
                'name' => 'MAED Logistics',
                'address' => 'Santo Domingo, República Dominicana',
                'phone' => '809-555-0100',
                'email' => 'info@maedlogistics.com',
                'website' => 'https://maedlogistics.com',
                'logo_path' => 'logo.png',
                'is_active' => true,
            ]
        );

        $this->command->info("Company created/found: {$company->name} (RNC: {$company->rnc})");
    }
}
