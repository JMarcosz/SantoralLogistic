<?php

use App\Models\User;

$user = User::create([
    'name' => 'Test Admin',
    'email' => 'test@maed.com',
    'password' => bcrypt('Test123!'),
    'email_verified_at' => now(),
    'must_change_password' => false,
]);

$user->assignRole('super_admin');

echo "Created user: {$user->email} with super_admin role\n";
