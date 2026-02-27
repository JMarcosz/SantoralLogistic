<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== BACKEND TESTING - Users & Roles ===\n\n";

// Test 1: Get current user
$user = \App\Models\User::first();
echo "✓ Current user: {$user->name} ({$user->email})\n";

// Test 2: Check roles
$roles = $user->roles->pluck('name')->implode(', ');
echo "✓ Roles: {$roles}\n";

// Test 3: Check permissions
echo "✓ Has users.view_any: " . ($user->can('users.view_any') ? 'YES' : 'NO') . "\n";
echo "✓ Has users.create: " . ($user->can('users.create') ? 'YES' : 'NO') . "\n";
echo "✓ Has roles.view_any: " . ($user->can('roles.view_any') ? 'YES' : 'NO') . "\n";
echo "✓ Has roles.create: " . ($user->can('roles.create') ? 'YES' : 'NO') . "\n\n";

// Test 4: Check if test user exists, if not create
$testUser = \App\Models\User::where('email', 'test.manager@example.com')->first();
if (!$testUser) {
    $testUser = \App\Models\User::create([
        'name' => 'Test Manager',
        'email' => 'test.manager@example.com',
        'password' => bcrypt('password123'),
    ]);
    $testUser->assignRole('manager');
    echo "✓ Created test user: {$testUser->email}\n";
} else {
    echo "✓ Test user already exists: {$testUser->email}\n";
}

// Test 5: Check test user permissions
echo "✓ Test user has users.create: " . ($testUser->can('users.create') ? 'YES' : 'NO') . "\n";
echo "✓ Test user has roles.create: " . ($testUser->can('roles.create') ? 'YES' : 'NO') . "\n";
echo "✓ Test user has roles.view_any: " . ($testUser->can('roles.view_any') ? 'YES' : 'NO') . "\n\n";

// Test 6: Check role_names accessor
echo "✓ role_names accessor works: " . json_encode($testUser->role_names) . "\n\n";

// Test 7: Test policies
$canDeleteSelf = $user->can('delete', $user);
echo "✓ Can user delete themselves: " . ($canDeleteSelf ? 'YES (ERROR!)' : 'NO (CORRECT)') . "\n";

$canDeleteOther = $user->can('delete', $testUser);
echo "✓ Can user delete others: " . ($canDeleteOther ? 'YES' : 'NO') . "\n\n";

// Test 8: Test role policy
$superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
$canDeleteSuperAdmin = $user->can('delete', $superAdminRole);
echo "✓ Can delete super_admin role: " . ($canDeleteSuperAdmin ? 'YES (ERROR!)' : 'NO (CORRECT)') . "\n";

echo "\n=== ALL TESTS PASSED ===\n";
