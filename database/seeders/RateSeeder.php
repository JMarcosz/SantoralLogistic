<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Port;
use App\Models\Rate;
use App\Models\ServiceType;
use App\Models\TransportMode;
use Illuminate\Database\Seeder;

class RateSeeder extends Seeder
{
    public function run(): void
    {
        // Get reference data
        $ports = Port::all()->keyBy('code');
        $modes = TransportMode::all()->keyBy('code');
        $services = ServiceType::all()->keyBy('code');
        $usd = Currency::where('code', 'USD')->first();

        if (!$usd || $ports->isEmpty() || $modes->isEmpty() || $services->isEmpty()) {
            $this->command->warn('Required reference data not found. Run port, mode, service, and currency seeders first.');
            return;
        }

        $rates = [
            // Air rates from SDQ to MIA
            [
                'origin' => 'DOSDQ',
                'destination' => 'USMIA',
                'mode' => 'AIR',
                'service' => 'EXP',
                'base_amount' => 2.50,
                'min_amount' => 150.00,
                'charge_basis' => 'per_kg',
            ],
            // Ocean rates from SDQ to MIA
            [
                'origin' => 'DOSDQ',
                'destination' => 'USMIA',
                'mode' => 'OCEAN',
                'service' => 'FCL',
                'base_amount' => 1500.00,
                'min_amount' => null,
                'charge_basis' => 'per_container',
            ],
            [
                'origin' => 'DOSDQ',
                'destination' => 'USMIA',
                'mode' => 'OCEAN',
                'service' => 'LCL',
                'base_amount' => 85.00,
                'min_amount' => 100.00,
                'charge_basis' => 'per_cbm',
            ],
            // Ground rates
            [
                'origin' => 'DOSDQ',
                'destination' => 'DOPOP',
                'mode' => 'GROUND',
                'service' => 'STD',
                'base_amount' => 250.00,
                'min_amount' => null,
                'charge_basis' => 'per_shipment',
            ],
        ];

        foreach ($rates as $data) {
            $origin = $ports->get($data['origin']);
            $destination = $ports->get($data['destination']);
            $mode = $modes->get($data['mode']);
            $service = $services->get($data['service']);

            if (!$origin || !$destination || !$mode || !$service) {
                $this->command->warn("Skipping rate: missing reference for {$data['origin']} -> {$data['destination']} ({$data['mode']}/{$data['service']})");
                continue;
            }

            Rate::firstOrCreate(
                [
                    'origin_port_id' => $origin->id,
                    'destination_port_id' => $destination->id,
                    'transport_mode_id' => $mode->id,
                    'service_type_id' => $service->id,
                ],
                [
                    'currency_id' => $usd->id,
                    'charge_basis' => $data['charge_basis'],
                    'base_amount' => $data['base_amount'],
                    'min_amount' => $data['min_amount'],
                    'valid_from' => now()->startOfYear(),
                    'valid_to' => now()->endOfYear(),
                    'is_active' => true,
                ]
            );

            $this->command->info("Rate '{$data['origin']} → {$data['destination']}' ({$data['mode']}/{$data['service']}) created or exists.");
        }

        $this->command->info('Rate seeding completed!');
    }
}
