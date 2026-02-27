<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates a basic Chart of Accounts structure for Dominican Republic.
     */
    public function run(): void
    {
        $accounts = [
            // ========== ASSETS (Activos) ==========
            [
                'code' => '1000',
                'name' => 'ACTIVOS',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_postable' => false, // Header account
                'parent_id' => null,
                'level' => 1,
            ],
            [
                'code' => '1100',
                'name' => 'Activos Corrientes',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_postable' => false,
                'parent_code' => '1000',
                'level' => 2,
            ],
            [
                'code' => '1110',
                'name' => 'Efectivo y Bancos',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_postable' => true, // Postable
                'parent_code' => '1100',
                'level' => 3,
            ],
            [
                'code' => '1200',
                'name' => 'Cuentas por Cobrar',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'requires_subsidiary' => true, // Requires customer link
                'parent_code' => '1100',
                'level' => 3,
            ],
            [
                'code' => '1300',
                'name' => 'Inventario',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'parent_code' => '1100',
                'level' => 3,
            ],
            [
                'code' => '1500',
                'name' => 'Activos Fijos',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_postable' => false,
                'parent_code' => '1000',
                'level' => 2,
            ],
            [
                'code' => '1510',
                'name' => 'Equipos y Muebles',
                'type' => 'asset',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'parent_code' => '1500',
                'level' => 3,
            ],

            // ========== LIABILITIES (Pasivos) ==========
            [
                'code' => '2000',
                'name' => 'PASIVOS',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_postable' => false,
                'parent_id' => null,
                'level' => 1,
            ],
            [
                'code' => '2100',
                'name' => 'Pasivos Corrientes',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_postable' => false,
                'parent_code' => '2000',
                'level' => 2,
            ],
            [
                'code' => '2110',
                'name' => 'Cuentas por Pagar',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_postable' => true,
                'requires_subsidiary' => true, // Requires vendor link
                'parent_code' => '2100',
                'level' => 3,
            ],
            [
                'code' => '2120',
                'name' => 'ITBIS por Pagar (18%)',
                'type' => 'liability',
                'normal_balance' => 'credit',
                'is_postable' => true,
                'parent_code' => '2100',
                'level' => 3,
            ],

            // ========== EQUITY (Patrimonio) ==========
            [
                'code' => '3000',
                'name' => 'PATRIMONIO',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'is_postable' => false,
                'parent_id' => null,
                'level' => 1,
            ],
            [
                'code' => '3100',
                'name' => 'Capital Social',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'is_postable' => true,
                'parent_code' => '3000',
                'level' => 2,
            ],
            [
                'code' => '3200',
                'name' => 'Utilidades Retenidas',
                'type' => 'equity',
                'normal_balance' => 'credit',
                'is_postable' => true,
                'parent_code' => '3000',
                'level' => 2,
            ],

            // ========== REVENUE (Ingresos) ==========
            [
                'code' => '4000',
                'name' => 'INGRESOS',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'is_postable' => false,
                'parent_id' => null,
                'level' => 1,
            ],
            [
                'code' => '4100',
                'name' => 'Ingresos por Ventas',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'is_postable' => true,
                'parent_code' => '4000',
                'level' => 2,
            ],
            [
                'code' => '4200',
                'name' => 'Ingresos por Servicios',
                'type' => 'revenue',
                'normal_balance' => 'credit',
                'is_postable' => true,
                'parent_code' => '4000',
                'level' => 2,
            ],

            // ========== EXPENSES (Gastos) ==========
            [
                'code' => '5000',
                'name' => 'COSTOS Y GASTOS',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_postable' => false,
                'parent_id' => null,
                'level' => 1,
            ],
            [
                'code' => '5100',
                'name' => 'Costo de Ventas',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'parent_code' => '5000',
                'level' => 2,
            ],
            [
                'code' => '5200',
                'name' => 'Gastos Operativos',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_postable' => false,
                'parent_code' => '5000',
                'level' => 2,
            ],
            [
                'code' => '5210',
                'name' => 'Gastos de Personal',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'parent_code' => '5200',
                'level' => 3,
            ],
            [
                'code' => '5220',
                'name' => 'Gastos Administrativos',
                'type' => 'expense',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'parent_code' => '5200',
                'level' => 3,
            ],
        ];

        // First pass: Create all accounts without parent_id
        $accountModels = [];
        foreach ($accounts as $accountData) {
            $parentCode = $accountData['parent_code'] ?? null;
            unset($accountData['parent_code']);

            $accountModels[$accountData['code']] = Account::create($accountData);
        }

        // Second pass: Update parent_id relationships
        foreach ($accounts as $accountData) {
            if (isset($accountData['parent_code'])) {
                $code = $accountData['code'];
                $parentCode = $accountData['parent_code'];

                if (isset($accountModels[$code]) && isset($accountModels[$parentCode])) {
                    $accountModels[$code]->update([
                        'parent_id' => $accountModels[$parentCode]->id,
                    ]);
                }
            }
        }

        $this->command->info('Chart of Accounts seeded successfully: ' . count($accounts) . ' accounts created.');
    }
}
