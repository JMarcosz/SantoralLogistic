<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceTypes = [
            [
                'code' => 'D2D',
                'name' => 'Door to Door',
                'description' => 'Servicio completo desde la puerta del origen hasta la puerta del destino.',
                'scope' => 'international',
                'default_incoterm' => 'DDP',
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'code' => 'P2D',
                'name' => 'Port to Door',
                'description' => 'Servicio desde el puerto de origen hasta la puerta del destino.',
                'scope' => 'international',
                'default_incoterm' => 'DAP',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'D2P',
                'name' => 'Door to Port',
                'description' => 'Servicio desde la puerta del origen hasta el puerto de destino.',
                'scope' => 'international',
                'default_incoterm' => 'FOB',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'P2P',
                'name' => 'Port to Port',
                'description' => 'Servicio entre puertos, sin transporte terrestre.',
                'scope' => 'international',
                'default_incoterm' => 'CIF',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'A2D',
                'name' => 'Airport to Door',
                'description' => 'Servicio desde el aeropuerto de origen hasta la puerta del destino.',
                'scope' => 'international',
                'default_incoterm' => 'DAP',
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'code' => 'LOCAL',
                'name' => 'Local Delivery',
                'description' => 'Servicio de entrega local dentro del mismo país.',
                'scope' => 'domestic',
                'default_incoterm' => null,
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($serviceTypes as $data) {
            ServiceType::firstOrCreate(
                ['code' => $data['code']],
                $data
            );

            $this->command->info("Service Type '{$data['code']}' - {$data['name']} created or already exists.");
        }

        $this->command->info('Service Type seeding completed successfully!');
    }
}
