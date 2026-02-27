<?php

namespace Database\Seeders;

use App\Models\FiscalSequence;
use Illuminate\Database\Seeder;

class FiscalSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing test data
        FiscalSequence::query()->delete();

        $now = now();

        // 1. Normal range - Low usage (20%)
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => '001',
            'ncf_from' => 'B01001000000001',
            'ncf_to' => 'B01001000001000',
            'current_ncf' => 'B01001000000200', // 20% used
            'valid_from' => $now->copy()->subMonths(6),
            'valid_to' => $now->copy()->addMonths(6),
            'is_active' => true,
        ]);

        // 2. Medium usage (50%) - Blue progress bar
        FiscalSequence::create([
            'ncf_type' => 'B02',
            'series' => '001',
            'ncf_from' => 'B02001000000001',
            'ncf_to' => 'B02001000001000',
            'current_ncf' => 'B02001000000500', // 50% used
            'valid_from' => $now->copy()->subMonths(3),
            'valid_to' => $now->copy()->addMonths(9),
            'is_active' => true,
        ]);

        // 3. High usage (75%) - Yellow progress bar
        FiscalSequence::create([
            'ncf_type' => 'B14',
            'series' => '001',
            'ncf_from' => 'B14001000000001',
            'ncf_to' => 'B14001000001000',
            'current_ncf' => 'B14001000000750', // 75% used
            'valid_from' => $now->copy()->subMonths(9),
            'valid_to' => $now->copy()->addMonths(3),
            'is_active' => true,
        ]);

        // 4. Near exhaustion (85%) - Orange progress bar + alert badge
        FiscalSequence::create([
            'ncf_type' => 'B15',
            'series' => '001',
            'ncf_from' => 'B15001000000001',
            'ncf_to' => 'B15001000001000',
            'current_ncf' => 'B15001000000850', // 85% used - triggers near_exhaustion
            'valid_from' => $now->copy()->subMonths(10),
            'valid_to' => $now->copy()->addMonths(2),
            'is_active' => true,
        ]);

        // 5. Critical usage (95%) - Red progress bar + alert badge
        FiscalSequence::create([
            'ncf_type' => 'B16',
            'series' => '001',
            'ncf_from' => 'B16001000000001',
            'ncf_to' => 'B16001000001000',
            'current_ncf' => 'B16001000000950', // 95% used
            'valid_from' => $now->copy()->subMonths(11),
            'valid_to' => $now->copy()->addMonths(1),
            'is_active' => true,
        ]);

        // 6. Exhausted range (100%)
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => '002',
            'ncf_from' => 'B01002000000001',
            'ncf_to' => 'B01002000000500',
            'current_ncf' => 'B01002000000500', // 100% exhausted
            'valid_from' => $now->copy()->subYear(),
            'valid_to' => $now->copy()->addMonths(6),
            'is_active' => true,
        ]);

        // 7. Near expiration (10 days left) - Shows expiration badge
        FiscalSequence::create([
            'ncf_type' => 'B02',
            'series' => '002',
            'ncf_from' => 'B02002000000001',
            'ncf_to' => 'B02002000001000',
            'current_ncf' => 'B02002000000100', // Low usage
            'valid_from' => $now->copy()->subMonths(11),
            'valid_to' => $now->copy()->addDays(10), // Expires in 10 days - triggers near_expiration
            'is_active' => true,
        ]);

        // 8. Critical - Both near exhaustion AND near expiration
        FiscalSequence::create([
            'ncf_type' => 'B14',
            'series' => '002',
            'ncf_from' => 'B14002000000001',
            'ncf_to' => 'B14002000001000',
            'current_ncf' => 'B14002000000900', // 90% used
            'valid_from' => $now->copy()->subMonths(11),
            'valid_to' => $now->copy()->addDays(5), // Expires in 5 days
            'is_active' => true,
        ]);

        // 9. Unused range - 0% usage
        FiscalSequence::create([
            'ncf_type' => 'B15',
            'series' => '002',
            'ncf_from' => 'B15002000000001',
            'ncf_to' => 'B15002000001000',
            'current_ncf' => null, // Never used
            'valid_from' => $now->copy()->startOfMonth(),
            'valid_to' => $now->copy()->addYear(),
            'is_active' => true,
        ]);

        // 10. Inactive range
        FiscalSequence::create([
            'ncf_type' => 'B16',
            'series' => '002',
            'ncf_from' => 'B16002000000001',
            'ncf_to' => 'B16002000001000',
            'current_ncf' => 'B16002000000300',
            'valid_from' => $now->copy()->subMonths(6),
            'valid_to' => $now->copy()->addMonths(6),
            'is_active' => false, // Deactivated
        ]);

        // 11. Range without series (null series)
        FiscalSequence::create([
            'ncf_type' => 'B01',
            'series' => null,
            'ncf_from' => 'B01000000001',
            'ncf_to' => 'B01000001000',
            'current_ncf' => 'B01000000400', // 40% used
            'valid_from' => $now->copy()->subMonths(4),
            'valid_to' => $now->copy()->addMonths(8),
            'is_active' => true,
        ]);

        // 12. Expired range (past valid_to date)
        FiscalSequence::create([
            'ncf_type' => 'B02',
            'series' => '003',
            'ncf_from' => 'B02003000000001',
            'ncf_to' => 'B02003000001000',
            'current_ncf' => 'B02003000000650',
            'valid_from' => $now->copy()->subYear(),
            'valid_to' => $now->copy()->subDays(10), // Already expired
            'is_active' => true,
        ]);

        $this->command->info('✓ Created 12 fiscal sequence test records');
        $this->command->info('  - Low usage: 1 range');
        $this->command->info('  - Medium usage (50%): 1 range');
        $this->command->info('  - High usage (75%): 1 range');
        $this->command->info('  - Near exhaustion (85-95%): 2 ranges');
        $this->command->info('  - Exhausted (100%): 1 range');
        $this->command->info('  - Near expiration: 1 range');
        $this->command->info('  - Critical (both alerts): 1 range');
        $this->command->info('  - Unused: 1 range');
        $this->command->info('  - Inactive: 1 range');
        $this->command->info('  - Expired: 1 range');
    }
}
