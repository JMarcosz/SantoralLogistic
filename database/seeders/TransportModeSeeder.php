<?php

namespace Database\Seeders;

use App\Models\TransportMode;
use Illuminate\Database\Seeder;

class TransportModeSeeder extends Seeder
{
    public function run(): void
    {
        $transportModes = [
            [
                'code' => 'AIR',
                'name' => 'Aéreo',
                'description' => 'Transporte aéreo de carga mediante aerolíneas.',
                'supports_awb' => true,
                'supports_bl' => false,
                'supports_pod' => true,
                'is_active' => true,
            ],
            [
                'code' => 'OCEAN',
                'name' => 'Marítimo',
                'description' => 'Transporte marítimo de carga por contenedores o carga suelta.',
                'supports_awb' => false,
                'supports_bl' => true,
                'supports_pod' => true,
                'is_active' => true,
            ],
            [
                'code' => 'GROUND',
                'name' => 'Terrestre',
                'description' => 'Transporte terrestre por camión o ferrocarril.',
                'supports_awb' => false,
                'supports_bl' => false,
                'supports_pod' => true,
                'is_active' => true,
            ],
            [
                'code' => 'RAIL',
                'name' => 'Ferroviario',
                'description' => 'Transporte ferroviario de carga.',
                'supports_awb' => false,
                'supports_bl' => false,
                'supports_pod' => true,
                'is_active' => true,
            ],
            [
                'code' => 'MULTIMODAL',
                'name' => 'Multimodal',
                'description' => 'Combinación de dos o más modos de transporte.',
                'supports_awb' => true,
                'supports_bl' => true,
                'supports_pod' => true,
                'is_active' => true,
            ],
        ];

        foreach ($transportModes as $data) {
            TransportMode::firstOrCreate(
                ['code' => $data['code']],
                $data
            );

            $this->command->info("Transport Mode '{$data['code']}' - {$data['name']} created or already exists.");
        }

        $this->command->info('Transport Mode seeding completed successfully!');
    }
}
