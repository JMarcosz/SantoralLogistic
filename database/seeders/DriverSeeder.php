<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $drivers = [
            [
                'name' => 'Juan Pérez',
                'phone' => '809-555-0101',
                'email' => 'juan.perez@maed.com',
                'license_number' => 'LIC-001234',
                'vehicle_plate' => 'A123456',
                'is_active' => true,
            ],
            [
                'name' => 'Carlos Rodríguez',
                'phone' => '809-555-0102',
                'email' => 'carlos.rodriguez@maed.com',
                'license_number' => 'LIC-002345',
                'vehicle_plate' => 'B234567',
                'is_active' => true,
            ],
            [
                'name' => 'Miguel Santos',
                'phone' => '809-555-0103',
                'email' => 'miguel.santos@maed.com',
                'license_number' => 'LIC-003456',
                'vehicle_plate' => 'C345678',
                'is_active' => true,
            ],
            [
                'name' => 'Roberto Martínez',
                'phone' => '809-555-0104',
                'email' => 'roberto.martinez@maed.com',
                'license_number' => 'LIC-004567',
                'vehicle_plate' => 'D456789',
                'is_active' => true,
            ],
            [
                'name' => 'Francisco García',
                'phone' => '809-555-0105',
                'email' => 'francisco.garcia@maed.com',
                'license_number' => 'LIC-005678',
                'vehicle_plate' => 'E567890',
                'is_active' => true,
            ],
            [
                'name' => 'Pedro López (Inactivo)',
                'phone' => '809-555-0106',
                'email' => 'pedro.lopez@maed.com',
                'license_number' => 'LIC-006789',
                'vehicle_plate' => 'F678901',
                'is_active' => false,
            ],
        ];

        foreach ($drivers as $driverData) {
            Driver::updateOrCreate(
                ['email' => $driverData['email']],
                $driverData
            );
        }

        $this->command->info('✓ Se crearon ' . count($drivers) . ' conductores de prueba.');
    }
}
