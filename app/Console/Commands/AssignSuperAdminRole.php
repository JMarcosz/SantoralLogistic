<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignSuperAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:assign-super-admin {--user= : The user ID or email to assign the role to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign the super_admin role to a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->option('user');

        if ($userIdentifier) {
            // Find by ID or email
            $user = is_numeric($userIdentifier)
                ? User::find($userIdentifier)
                : User::where('email', $userIdentifier)->first();
        } else {
            // Get the first user if no identifier provided
            $user = User::first();
        }

        if (!$user) {
            $this->error('No user found.');
            return Command::FAILURE;
        }

        if ($user->hasRole('super_admin')) {
            $this->info("User '{$user->email}' already has the super_admin role.");
            return Command::SUCCESS;
        }

        $user->assignRole('super_admin');
        $this->info("Role 'super_admin' assigned to user: {$user->email}");

        return Command::SUCCESS;
    }
}
