<?php

namespace Database\Seeders;

use App\Models\PackageType;
use Illuminate\Database\Seeder;

class PackageTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packageTypes = [
            [
                'code' => 'BOX',
                'name' => 'Caja Estándar',
                'description' => 'Caja de cartón estándar para envíos generales.',
                'category' => 'box',
                'length_cm' => 40,
                'width_cm' => 30,
                'height_cm' => 25,
                'max_weight_kg' => 25,
                'is_container' => false,
                'is_active' => true,
            ],
            [
                'code' => 'BOXSM',
                'name' => 'Caja Pequeña',
                'description' => 'Caja pequeña para artículos ligeros.',
                'category' => 'box',
                'length_cm' => 25,
                'width_cm' => 20,
                'height_cm' => 15,
                'max_weight_kg' => 10,
                'is_container' => false,
                'is_active' => true,
            ],
            [
                'code' => 'BOXLG',
                'name' => 'Caja Grande',
                'description' => 'Caja grande para artículos voluminosos.',
                'category' => 'box',
                'length_cm' => 60,
                'width_cm' => 40,
                'height_cm' => 40,
                'max_weight_kg' => 50,
                'is_container' => false,
                'is_active' => true,
            ],
            [
                'code' => 'PALLET',
                'name' => 'Pallet Estándar',
                'description' => 'Pallet estándar 120x80 cm (Europallet).',
                'category' => 'pallet',
                'length_cm' => 120,
                'width_cm' => 80,
                'height_cm' => 15,
                'max_weight_kg' => 1500,
                'is_container' => false,
                'is_active' => true,
            ],
            [
                'code' => 'CONT20',
                'name' => 'Contenedor 20\'',
                'description' => 'Contenedor marítimo estándar de 20 pies.',
                'category' => 'container',
                'length_cm' => 590,
                'width_cm' => 235,
                'height_cm' => 239,
                'max_weight_kg' => 28000,
                'is_container' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CONT40',
                'name' => 'Contenedor 40\'',
                'description' => 'Contenedor marítimo estándar de 40 pies.',
                'category' => 'container',
                'length_cm' => 1203,
                'width_cm' => 235,
                'height_cm' => 239,
                'max_weight_kg' => 30000,
                'is_container' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CONT40HC',
                'name' => 'Contenedor 40\' High Cube',
                'description' => 'Contenedor marítimo de 40 pies con altura extra.',
                'category' => 'container',
                'length_cm' => 1203,
                'width_cm' => 235,
                'height_cm' => 269,
                'max_weight_kg' => 30000,
                'is_container' => true,
                'is_active' => true,
            ],
            [
                'code' => 'ENVELOPE',
                'name' => 'Sobre',
                'description' => 'Sobre para documentos y artículos planos.',
                'category' => 'envelope',
                'length_cm' => 35,
                'width_cm' => 25,
                'height_cm' => 2,
                'max_weight_kg' => 1,
                'is_container' => false,
                'is_active' => true,
            ],
            [
                'code' => 'DRUM',
                'name' => 'Tambor/Barril',
                'description' => 'Tambor metálico o plástico para líquidos.',
                'category' => 'other',
                'length_cm' => 60,
                'width_cm' => 60,
                'height_cm' => 90,
                'max_weight_kg' => 250,
                'is_container' => false,
                'is_active' => true,
            ],
            [
                'code' => 'CRATE',
                'name' => 'Jaula/Crate',
                'description' => 'Jaula de madera para cargas especiales.',
                'category' => 'other',
                'length_cm' => null,
                'width_cm' => null,
                'height_cm' => null,
                'max_weight_kg' => null,
                'is_container' => false,
                'is_active' => true,
            ],
        ];

        foreach ($packageTypes as $data) {
            PackageType::firstOrCreate(
                ['code' => $data['code']],
                $data
            );

            $this->command->info("Package Type '{$data['code']}' - {$data['name']} created or already exists.");
        }

        $this->command->info('Package Type seeding completed successfully!');
    }
}
