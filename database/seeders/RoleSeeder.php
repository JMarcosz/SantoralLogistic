<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * The initial roles for the application.
     *
     * @var array<string, array<string, string>>
     */
    protected array $roles = [
        'super_admin' => [
            'name' => 'super_admin',
            'display_name' => 'Super Administrador',
            'description' => 'Acceso completo a todas las funcionalidades del sistema',
        ],
        'manager' => [
            'name' => 'manager',
            'display_name' => 'Gerente',
            'description' => 'Gestión general de operaciones y reportes',
        ],
        'operations_manager' => [
            'name' => 'operations_manager',
            'display_name' => 'Gerente de Operaciones',
            'description' => 'Supervisión de operaciones logísticas y almacén',
        ],
        'sales' => [
            'name' => 'sales',
            'display_name' => 'Ventas',
            'description' => 'Gestión de ventas y cotizaciones',
        ],
        'customer_service' => [
            'name' => 'customer_service',
            'display_name' => 'Servicio al Cliente',
            'description' => 'Atención al cliente y seguimiento de pedidos',
        ],
        'warehouse' => [
            'name' => 'warehouse',
            'display_name' => 'Almacén',
            'description' => 'Gestión de inventario y almacenamiento',
        ],
        'dispatch' => [
            'name' => 'dispatch',
            'display_name' => 'Despacho',
            'description' => 'Gestión de envíos y entregas',
        ],
        'accounting' => [
            'name' => 'accounting',
            'display_name' => 'Contabilidad',
            'description' => 'Gestión financiera y facturación',
        ],
    ];

    /**
     * Permission assignments for each role.
     * Note: super_admin is not included here because Gate::before() in 
     * AppServiceProvider automatically grants all permissions to super_admin.
     * 
     * @var array<string, array<string>>
     */
    protected array $rolePermissions = [
        'manager' => [
            // Company Settings - can manage but not force delete
            'company_settings.view_any',
            'company_settings.view',
            'company_settings.create',
            'company_settings.update',
            'company_settings.delete',

            // Users - can fully manage
            'users.view_any',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Roles - can view only
            'roles.view_any',
            'roles.view',

            // Currencies - can fully manage
            'currencies.view_any',
            'currencies.view',
            'currencies.create',
            'currencies.update',
            'currencies.delete',
        ],
        'operations_manager' => [
            // View only
            'company_settings.view_any',
            'company_settings.view',
        ],
        'sales' => [
            // View only
            'company_settings.view_any',
            'company_settings.view',

            // Quotes
            'quotes.view_any',
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.delete',
            'quotes.send',
            'quotes.approve',
            'quotes.reject',
            'quotes.convert_to_shipping_order',

            // Customers/Contacts
            'customers.view_any',
            'customers.view',
            'customers.create',
            'customers.update',
            'contacts.view_any',
            'contacts.view',
            'contacts.create',
            'contacts.update',
        ],
        'customer_service' => [
            // View only
            'company_settings.view_any',
            'company_settings.view',
        ],
        'warehouse' => [
            // View only
            'company_settings.view_any',
            'company_settings.view',
        ],
        'dispatch' => [
            // View only
            'company_settings.view_any',
            'company_settings.view',
        ],
        'accounting' => [
            // View only for company settings
            'company_settings.view_any',
            'company_settings.view',

            // Full access to accounting features
            'accounting.view',
            'accounting.manage',
            'accounting.post',

            // Journal Entries - full access
            'journal_entries.view',
            'journal_entries.create',
            'journal_entries.edit',
            'journal_entries.delete',
            'journal_entries.post',
            'journal_entries.reverse',

            // Fiscal Invoices - full access
            'invoices.view_any',
            'invoices.view',
            'invoices.create',
            'invoices.cancel',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        // Wrapped in try-catch because the cache table may not exist yet
        // during migrate:fresh --seed (Spatie uses database cache driver)
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            $this->command->warn('Could not clear permission cache: ' . $e->getMessage());
        }

        foreach ($this->roles as $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => 'web'],
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'web',
                ]
            );

            $this->command->info("Role '{$roleData['display_name']}' created or already exists.");

            // Assign permissions to role
            $permissions = $this->rolePermissions[$roleData['name']] ?? [];
            if (!empty($permissions)) {
                $role->syncPermissions($permissions);
                $this->command->info("  -> Assigned " . count($permissions) . " permissions to '{$roleData['name']}'");
            }
        }

        $this->command->info('All roles have been seeded successfully!');
    }

    /**
     * Get all role names.
     *
     * @return array<string>
     */
    public static function getRoleNames(): array
    {
        return [
            'super_admin',
            'manager',
            'operations_manager',
            'sales',
            'customer_service',
            'warehouse',
            'dispatch',
            'accounting',
        ];
    }
}
