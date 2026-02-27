<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TestAdminSeeder extends Seeder
{
    /**
     * Create a test admin user for development/testing.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@maed.com'],
            [
                'name' => 'Test Admin',
                'password' => bcrypt('Test123!'),
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );

        if (!$user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
        }

        $this->command->info("Test admin created: test@maed.com / Test123!");
    }
}
