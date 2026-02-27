<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ProductService;
use Illuminate\Database\Seeder;

class ProductServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Get USD currency if exists
        $usd = Currency::where('code', 'USD')->first();
        $usdId = $usd?->id;

        $items = [
            // Services
            [
                'code' => 'FRT-AIR',
                'name' => 'Flete Aéreo',
                'description' => 'Servicio de transporte aéreo de carga.',
                'type' => 'service',
                'uom' => 'kg',
                'default_currency_id' => $usdId,
                'default_unit_price' => null,
                'taxable' => true,
            ],
            [
                'code' => 'FRT-OCN',
                'name' => 'Flete Marítimo',
                'description' => 'Servicio de transporte marítimo de carga.',
                'type' => 'service',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => null,
                'taxable' => true,
            ],
            [
                'code' => 'FRT-GND',
                'name' => 'Flete Terrestre',
                'description' => 'Servicio de transporte terrestre de carga.',
                'type' => 'service',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => null,
                'taxable' => true,
            ],
            [
                'code' => 'HANDLING',
                'name' => 'Manejo de Carga',
                'description' => 'Servicio de manipulación y manejo de mercancías.',
                'type' => 'service',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => 75.0000,
                'taxable' => true,
            ],
            [
                'code' => 'STORAGE',
                'name' => 'Almacenaje',
                'description' => 'Servicio de almacenamiento en bodega.',
                'type' => 'service',
                'uom' => 'day',
                'default_currency_id' => $usdId,
                'default_unit_price' => 5.0000,
                'taxable' => true,
            ],
            [
                'code' => 'CUSTOMS',
                'name' => 'Despacho Aduanal',
                'description' => 'Servicio de gestión y trámites aduanales.',
                'type' => 'service',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => 150.0000,
                'taxable' => true,
            ],
            [
                'code' => 'INSURANCE',
                'name' => 'Seguro de Carga',
                'description' => 'Seguro contra daños o pérdida de mercancía.',
                'type' => 'service',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => null,
                'taxable' => false,
            ],
            // Fees
            [
                'code' => 'FSC',
                'name' => 'Fuel Surcharge',
                'description' => 'Recargo por combustible.',
                'type' => 'fee',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => null,
                'taxable' => true,
            ],
            [
                'code' => 'SSC',
                'name' => 'Security Surcharge',
                'description' => 'Recargo por seguridad.',
                'type' => 'fee',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => 25.0000,
                'taxable' => true,
            ],
            [
                'code' => 'DOC-FEE',
                'name' => 'Documentation Fee',
                'description' => 'Cargo por documentación y trámites.',
                'type' => 'fee',
                'uom' => 'shipment',
                'default_currency_id' => $usdId,
                'default_unit_price' => 35.0000,
                'taxable' => true,
            ],
            [
                'code' => 'THC',
                'name' => 'Terminal Handling Charge',
                'description' => 'Cargo por manejo en terminal.',
                'type' => 'fee',
                'uom' => 'container',
                'default_currency_id' => $usdId,
                'default_unit_price' => 250.0000,
                'taxable' => true,
            ],
            // Products
            [
                'code' => 'PKG-BOX',
                'name' => 'Caja de Embalaje',
                'description' => 'Material de embalaje - caja de cartón.',
                'type' => 'product',
                'uom' => 'unit',
                'default_currency_id' => $usdId,
                'default_unit_price' => 5.0000,
                'taxable' => true,
            ],
            [
                'code' => 'PKG-PALLET',
                'name' => 'Pallet',
                'description' => 'Pallet de madera para carga.',
                'type' => 'product',
                'uom' => 'unit',
                'default_currency_id' => $usdId,
                'default_unit_price' => 15.0000,
                'taxable' => true,
            ],
        ];

        foreach ($items as $data) {
            ProductService::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );

            $this->command->info("Product/Service '{$data['code']}' - {$data['name']} created or already exists.");
        }

        $this->command->info('Products & Services seeding completed!');
    }
}
