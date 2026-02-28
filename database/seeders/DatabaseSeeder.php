<?php

namespace Database\Seeders;

use App\Models\Rate;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First, seed permissions, then roles
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            CompanySeeder::class,
            CurrencySeeder::class,
            CustomerSeeder::class,
            PortSeeder::class,
            DriverSeeder::class,
            FiscalSequenceSeeder::class,
            InvoicePermissionSeeder::class,
            PackageTypeSeeder::class,
            PreInvoiceSeeder::class,
            ProductServiceSeeder::class,
            RateSeeder::class,
            ServiceTypeSeeder::class,
            TermsSeeder::class,
            TransportModeSeeder::class,
            WarehouseInventorySeeder::class,


        ]);

        // Create or get the first admin user
        $user = User::firstOrCreate(
            ['email' => 'desarrollo@stonenovatech.com'],
            [
                'name' => 'Super Admin',
                'password' => 'Joker@7890',
                'email_verified_at' => now(),
            ]
        );

        // Assign super_admin role to the first user
        if (!$user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
            $this->command->info("Role 'super_admin' assigned to user: {$user->email}");
        }
    }
}
