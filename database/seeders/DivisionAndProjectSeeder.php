<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Project;
use Illuminate\Database\Seeder;

class DivisionAndProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Divisions
        $divisions = [
            ['name' => 'Dominicana', 'code' => 'DOM', 'is_active' => true],
            ['name' => 'USA', 'code' => 'USA', 'is_active' => true],
            ['name' => 'Haiti', 'code' => 'HAI', 'is_active' => true],
        ];

        foreach ($divisions as $div) {
            Division::firstOrCreate(
                ['code' => $div['code']],
                ['name' => $div['name'], 'is_active' => $div['is_active']]
            );
        }

        $this->command->info('Divisions seeded successfully.');


        // Projects
        $projects = [
            ['name' => 'Mineria', 'code' => 'MIN', 'is_active' => true],
            ['name' => 'Energia Solar', 'code' => 'SOL', 'is_active' => true],
            ['name' => 'Infraestructura Vial', 'code' => 'VIA', 'is_active' => true],
            ['name' => 'Expansión Portuaria', 'code' => 'PRT', 'is_active' => true],
        ];

        foreach ($projects as $proj) {
            Project::firstOrCreate(
                ['code' => $proj['code']],
                ['name' => $proj['name'], 'is_active' => $proj['is_active']]
            );
        }

        $this->command->info('Projects seeded successfully.');
    }
}
