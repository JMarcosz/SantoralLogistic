<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ports = [
            // Air ports (Airports)
            [
                'code' => 'USMIA',
                'name' => 'Miami International Airport',
                'country' => 'United States',
                'city' => 'Miami',
                'unlocode' => 'USMIA',
                'iata_code' => 'MIA',
                'type' => 'air',
                'timezone' => 'America/New_York',
                'is_active' => true,
            ],
            [
                'code' => 'AEDXB',
                'name' => 'Dubai International Airport',
                'country' => 'United Arab Emirates',
                'city' => 'Dubai',
                'unlocode' => 'AEDXB',
                'iata_code' => 'DXB',
                'type' => 'air',
                'timezone' => 'Asia/Dubai',
                'is_active' => true,
            ],
            [
                'code' => 'HKHKG',
                'name' => 'Hong Kong International Airport',
                'country' => 'Hong Kong',
                'city' => 'Hong Kong',
                'unlocode' => 'HKHKG',
                'iata_code' => 'HKG',
                'type' => 'air',
                'timezone' => 'Asia/Hong_Kong',
                'is_active' => true,
            ],
            [
                'code' => 'MXMEX',
                'name' => 'Mexico City International Airport',
                'country' => 'Mexico',
                'city' => 'Mexico City',
                'unlocode' => 'MXMEX',
                'iata_code' => 'MEX',
                'type' => 'air',
                'timezone' => 'America/Mexico_City',
                'is_active' => true,
            ],

            // Ocean ports (Seaports)
            [
                'code' => 'USNYC',
                'name' => 'Port of New York and New Jersey',
                'country' => 'United States',
                'city' => 'New York',
                'unlocode' => 'USNYC',
                'iata_code' => null,
                'type' => 'ocean',
                'timezone' => 'America/New_York',
                'is_active' => true,
            ],
            [
                'code' => 'CNSHA',
                'name' => 'Port of Shanghai',
                'country' => 'China',
                'city' => 'Shanghai',
                'unlocode' => 'CNSHA',
                'iata_code' => null,
                'type' => 'ocean',
                'timezone' => 'Asia/Shanghai',
                'is_active' => true,
            ],
            [
                'code' => 'DEHAM',
                'name' => 'Port of Hamburg',
                'country' => 'Germany',
                'city' => 'Hamburg',
                'unlocode' => 'DEHAM',
                'iata_code' => null,
                'type' => 'ocean',
                'timezone' => 'Europe/Berlin',
                'is_active' => true,
            ],
            [
                'code' => 'NLRTM',
                'name' => 'Port of Rotterdam',
                'country' => 'Netherlands',
                'city' => 'Rotterdam',
                'unlocode' => 'NLRTM',
                'iata_code' => null,
                'type' => 'ocean',
                'timezone' => 'Europe/Amsterdam',
                'is_active' => true,
            ],

            // Ground locations
            [
                'code' => 'DOSDQ',
                'name' => 'Santo Domingo Ground Terminal',
                'country' => 'Dominican Republic',
                'city' => 'Santo Domingo',
                'unlocode' => 'DOSDQ',
                'iata_code' => null,
                'type' => 'ground',
                'timezone' => 'America/Santo_Domingo',
                'is_active' => true,
            ],
            [
                'code' => 'DOPOP',
                'name' => 'Puerto Plata Port',
                'country' => 'Dominican Republic',
                'city' => 'Puerto Plata',
                'unlocode' => 'DOPOP',
                'iata_code' => null,
                'type' => 'ocean',
                'timezone' => 'America/Santo_Domingo',
                'is_active' => true,
            ],
        ];

        foreach ($ports as $portData) {
            Port::firstOrCreate(
                ['code' => $portData['code']],
                $portData
            );

            $this->command->info("Port '{$portData['code']}' - {$portData['name']} created or already exists.");
        }

        $this->command->info('Port seeding completed successfully!');
    }
}
