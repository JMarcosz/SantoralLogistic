<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InvoicePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define invoice permissions
        $permissions = [
            'invoices.view_any' => 'View invoice list',
            'invoices.view' => 'View invoice details',
            'invoices.create' => 'Create invoices from pre-invoices',
            'invoices.cancel' => 'Cancel issued invoices',
            'invoices.print' => 'Print/download invoice PDFs',
            'invoices.email' => 'Email invoices to customers',
            'invoices.export' => 'Batch export invoices',
            'invoices.analytics' => 'View invoice analytics',
        ];

        // Create permissions
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Assign all permissions to super admin role (if exists)
        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_keys($permissions));
        }

        // Assign basic permissions to admin role (if exists)
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'invoices.view_any',
                'invoices.view',
                'invoices.print',
                'invoices.email',
                'invoices.export',
                'invoices.analytics',
            ]);
        }

        $this->command->info('Invoice permissions created successfully!');
    }
}
