<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class TermsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Payment Terms
        Term::updateOrCreate(
            ['type' => Term::TYPE_PAYMENT, 'code' => 'NET30'],
            [
                'name' => 'Pago 30 días',
                'description' => 'Términos de pago estándar de 30 días',
                'body' => 'El pago total debe realizarse dentro de los 30 días calendario siguientes a la fecha de facturación.',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Term::updateOrCreate(
            ['type' => Term::TYPE_PAYMENT, 'code' => 'NET15'],
            [
                'name' => 'Pago 15 días',
                'description' => 'Términos de pago de 15 días',
                'body' => 'El pago total debe realizarse dentro de los 15 días calendario siguientes a la fecha de facturación.',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        Term::updateOrCreate(
            ['type' => Term::TYPE_PAYMENT, 'code' => 'COD'],
            [
                'name' => 'Contra Entrega',
                'description' => 'Pago contra entrega de mercancía',
                'body' => 'El pago debe realizarse al momento de la entrega de la mercancía.',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        Term::updateOrCreate(
            ['type' => Term::TYPE_PAYMENT, 'code' => 'PREPAID'],
            [
                'name' => 'Prepago',
                'description' => 'Pago anticipado requerido',
                'body' => 'El pago total debe realizarse antes del embarque de la mercancía.',
                'is_default' => false,
                'is_active' => true,
            ]
        );

        // Quote Footer Terms
        Term::updateOrCreate(
            ['type' => Term::TYPE_QUOTE_FOOTER, 'code' => 'QUOTE_STD'],
            [
                'name' => 'Términos Estándar de Cotización',
                'description' => 'Términos legales estándar para cotizaciones',
                'body' => "TÉRMINOS Y CONDICIONES DE COTIZACIÓN

1. VALIDEZ: Esta cotización es válida por el período indicado. Los precios están sujetos a cambio sin previo aviso después de dicho período.

2. TARIFAS: Las tarifas indicadas no incluyen servicios adicionales, almacenaje extendido, inspecciones o cargos gubernamentales a menos que se especifique.

3. SEGURO: El seguro de carga no está incluido a menos que se solicite expresamente. Recomendamos asegurar toda mercancía.

4. DOCUMENTACIÓN: El cliente es responsable de proporcionar toda la documentación requerida para el despacho aduanero.

5. FUERZA MAYOR: No somos responsables por retrasos o daños causados por circunstancias fuera de nuestro control.

6. RECLAMACIONES: Cualquier reclamación debe presentarse por escrito dentro de los 7 días posteriores a la entrega.",
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // Shipping Order Footer Terms
        Term::updateOrCreate(
            ['type' => Term::TYPE_SO_FOOTER, 'code' => 'SO_STD'],
            [
                'name' => 'Términos Estándar de Orden de Envío',
                'description' => 'Términos legales estándar para órdenes de envío',
                'body' => "TÉRMINOS Y CONDICIONES DE ENVÍO

1. RESPONSABILIDAD: Nuestra responsabilidad está limitada según las convenciones internacionales aplicables (CMR, Convenio de Varsovia, Reglas de La Haya-Visby).

2. EMBALAJE: El remitente es responsable del embalaje adecuado de la mercancía. No somos responsables por daños debido a embalaje inadecuado.

3. PROHIBICIONES: El cliente garantiza que el envío no contiene mercancías peligrosas, ilegales o prohibidas sin la debida declaración y documentación.

4. ADUANAS: Los derechos, impuestos y cargos aduaneros son responsabilidad del destinatario o remitente según el INCOTERM acordado.

5. ALMACENAJE: El almacenaje gratuito está limitado a 3 días. Después de este período, se aplicarán cargos diarios.

6. RECLAMACIONES: Las reclamaciones por daños deben notificarse inmediatamente al recibir la mercancía y formalizarse por escrito en un plazo de 48 horas.",
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // Invoice Footer Terms (for future use)
        Term::updateOrCreate(
            ['type' => Term::TYPE_INVOICE_FOOTER, 'code' => 'INV_STD'],
            [
                'name' => 'Términos Estándar de Factura',
                'description' => 'Términos legales estándar para facturas',
                'body' => "TÉRMINOS Y CONDICIONES DE PAGO

1. VENCIMIENTO: El pago debe realizarse según los términos acordados. El incumplimiento generará intereses moratorios.

2. DISPUTAS: Cualquier disputa sobre esta factura debe comunicarse por escrito dentro de los 5 días hábiles.

3. MONEDA: El pago debe realizarse en la moneda indicada en la factura.

4. TRANSFERENCIAS: Para pagos por transferencia, incluya el número de factura como referencia.

5. COMPROBANTE: Conserve esta factura como comprobante fiscal.",
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }
}
