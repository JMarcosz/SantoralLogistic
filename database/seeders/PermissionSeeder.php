<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * All permissions organized by module.
     *
     * @var array<string, array<string>>
     */
    protected array $permissions = [
        // CompanySetting permissions
        'company_settings' => [
            'company_settings.view_any',
            'company_settings.view',
            'company_settings.create',
            'company_settings.update',
            'company_settings.delete',
            'company_settings.restore',
            'company_settings.force_delete',
        ],

        // Users permissions
        'users' => [
            'users.view_any',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ],

        // Roles permissions
        'roles' => [
            'roles.view_any',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ],

        // Currencies permissions
        'currencies' => [
            'currencies.view_any',
            'currencies.view',
            'currencies.create',
            'currencies.update',
            'currencies.delete',
            'currencies.restore',
            'currencies.force_delete',
        ],

        // Ports permissions
        'ports' => [
            'ports.view_any',
            'ports.view',
            'ports.create',
            'ports.update',
            'ports.delete',
            'ports.restore',
            'ports.force_delete',
        ],

        // Service Types permissions
        'service_types' => [
            'service_types.view_any',
            'service_types.view',
            'service_types.create',
            'service_types.update',
            'service_types.delete',
            'service_types.restore',
            'service_types.force_delete',
        ],

        // Package Types permissions
        'package_types' => [
            'package_types.view_any',
            'package_types.view',
            'package_types.create',
            'package_types.update',
            'package_types.delete',
            'package_types.restore',
            'package_types.force_delete',
        ],

        // Transport Modes permissions
        'transport_modes' => [
            'transport_modes.view_any',
            'transport_modes.view',
            'transport_modes.create',
            'transport_modes.update',
            'transport_modes.delete',
            'transport_modes.restore',
            'transport_modes.force_delete',
        ],

        // Products & Services permissions
        'products_services' => [
            'products_services.view_any',
            'products_services.view',
            'products_services.create',
            'products_services.update',
            'products_services.delete',
            'products_services.restore',
            'products_services.force_delete',
        ],

        // Rates permissions
        'rates' => [
            'rates.view_any',
            'rates.view',
            'rates.create',
            'rates.update',
            'rates.delete',
            'rates.restore',
            'rates.force_delete',
        ],

        // Customers permissions
        'customers' => [
            'customers.view_any',
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'customers.restore',
            'customers.force_delete',
        ],

        // Contacts permissions
        'contacts' => [
            'contacts.view_any',
            'contacts.view',
            'contacts.create',
            'contacts.update',
            'contacts.delete',
            'contacts.restore',
            'contacts.force_delete',
        ],

        // Quotes permissions
        'quotes' => [
            'quotes.view_any',
            'quotes.view',
            'quotes.create',
            'quotes.update',
            'quotes.delete',
            'quotes.send',
            'quotes.approve',
            'quotes.reject',
            'quotes.convert_to_shipping_order',
            'quotes.restore',
            'quotes.force_delete',
        ],

        // Shipping Orders permissions
        'shipping_orders' => [
            'shipping_orders.view_any',
            'shipping_orders.view',
            'shipping_orders.create',
            'shipping_orders.update',
            'shipping_orders.delete',
            'shipping_orders.restore',
            'shipping_orders.force_delete',
        ],

        // Terms permissions
        'terms' => [
            'terms.view_any',
            'terms.view',
            'terms.create',
            'terms.update',
            'terms.delete',
        ],

        // Drivers permissions
        'drivers' => [
            'drivers.view_any',
            'drivers.view',
            'drivers.create',
            'drivers.update',
            'drivers.delete',
        ],

        // Pickup Orders permissions
        'pickup_orders' => [
            'pickup_orders.view_any',
            'pickup_orders.view',
            'pickup_orders.create',
            'pickup_orders.update',
            'pickup_orders.delete',
            'pickup_orders.assign_driver',
            'pickup_orders.change_status',
        ],

        // Delivery Orders permissions
        'delivery_orders' => [
            'delivery_orders.view_any',
            'delivery_orders.view',
            'delivery_orders.create',
            'delivery_orders.update',
            'delivery_orders.delete',
            'delivery_orders.assign_driver',
            'delivery_orders.change_status',
        ],

        // Warehouses permissions
        'warehouses' => [
            'warehouses.view_any',
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.delete',
        ],

        // Locations permissions
        'locations' => [
            'locations.view_any',
            'locations.view',
            'locations.create',
            'locations.update',
            'locations.delete',
        ],

        // Warehouse Receipts permissions
        'warehouse_receipts' => [
            'warehouse_receipts.view_any',
            'warehouse_receipts.view',
            'warehouse_receipts.create',
            'warehouse_receipts.update',
            'warehouse_receipts.delete',
            'warehouse_receipts.receive',
            'warehouse_receipts.close',
            'warehouse_receipts.cancel',
        ],

        // Inventory permissions
        'inventory' => [
            'inventory.view_any',
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
        ],

        // Warehouse Orders permissions
        'warehouse_orders' => [
            'warehouse_orders.view_any',
            'warehouse_orders.view',
            'warehouse_orders.create',
            'warehouse_orders.update',
            'warehouse_orders.dispatch',
            'warehouse_orders.cancel',
        ],

        // Cycle Counts permissions
        'cycle_counts' => [
            'cycle_counts.view_any',
            'cycle_counts.view',
            'cycle_counts.create',
            'cycle_counts.update',
            'cycle_counts.complete',
            'cycle_counts.cancel',
        ],

        // PreInvoices permissions
        'pre_invoices' => [
            'pre_invoices.view_any',
            'pre_invoices.view',
            'pre_invoices.create',
            'pre_invoices.update',
            'pre_invoices.delete',
        ],

        // Billing / Accounts Receivable permissions
        'billing' => [
            'billing.ar.view',
            'billing.ar.export',
            'billing.payments.create',
            'billing.payments.approve',
            'billing.payments.void',
        ],

        // Fiscal Invoices permissions
        'invoices' => [
            'invoices.view_any',
            'invoices.view',
            'invoices.create',
            'invoices.cancel',
        ],

        // Payments permissions
        'payments' => [
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',
            'payments.post',
            'payments.void',
        ],

        // Fiscal Sequences permissions
        'fiscal_sequences' => [
            'fiscal_sequences.manage',
        ],

        // Accounting permissions
        'accounting' => [
            'accounting.view',
            'accounting.manage',
            'accounting.post',
            'accounting.close_period',
        ],

        // Journal Entries permissions
        'journal_entries' => [
            'journal_entries.view',
            'journal_entries.create',
            'journal_entries.edit',
            'journal_entries.delete',
            'journal_entries.post',
            'journal_entries.reverse',
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

        $createdCount = 0;
        $existingCount = 0;

        foreach ($this->permissions as $module => $modulePermissions) {
            foreach ($modulePermissions as $permission) {
                $created = Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => 'web']
                );

                if ($created->wasRecentlyCreated) {
                    $createdCount++;
                    $this->command->info("Permission '{$permission}' created.");
                } else {
                    $existingCount++;
                }
            }
        }

        $this->command->info("Permissions seeding complete: {$createdCount} created, {$existingCount} already existed.");
    }

    /**
     * Get all permission names.
     *
     * @return array<string>
     */
    public static function getAllPermissions(): array
    {
        return [
            // Company Settings
            'company_settings.view_any',
            'company_settings.view',
            'company_settings.create',
            'company_settings.update',
            'company_settings.delete',
            'company_settings.restore',
            'company_settings.force_delete',

            // Users
            'users.view_any',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Roles
            'roles.view_any',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            // Currencies
            'currencies.view_any',
            'currencies.view',
            'currencies.create',
            'currencies.update',
            'currencies.delete',
            'currencies.restore',
            'currencies.force_delete',

            // Ports
            'ports.view_any',
            'ports.view',
            'ports.create',
            'ports.update',
            'ports.delete',
            'ports.restore',
            'ports.force_delete',
        ];
    }

    /**
     * Get permissions by module.
     *
     * @param string $module
     * @return array<string>
     */
    public static function getPermissionsByModule(string $module): array
    {
        $permissions = [
            'company_settings' => [
                'company_settings.view_any',
                'company_settings.view',
                'company_settings.create',
                'company_settings.update',
                'company_settings.delete',
                'company_settings.restore',
                'company_settings.force_delete',
            ],
            'users' => [
                'users.view_any',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
            ],
            'roles' => [
                'roles.view_any',
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',
            ],
            'currencies' => [
                'currencies.view_any',
                'currencies.view',
                'currencies.create',
                'currencies.update',
                'currencies.delete',
                'currencies.restore',
                'currencies.force_delete',
            ],
            'ports' => [
                'ports.view_any',
                'ports.view',
                'ports.create',
                'ports.update',
                'ports.delete',
                'ports.restore',
                'ports.force_delete',
            ],
        ];

        return $permissions[$module] ?? [];
    }
}
