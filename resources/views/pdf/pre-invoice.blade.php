<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Factura {{ $preInvoice->number }}</title>
    <style>
        /** CONFIGURACIÓN GENERAL 
            Usamos fuentes sans-serif limpias. DejaVu Sans es segura para UTF-8 en DOMPDF.
        */
        @page {
            margin: 0cm 0cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #374151;
            margin-top: 3cm;
            margin-bottom: 2cm;
            background-color: #fff;
        }

        /* UTILIDADES */
        .w-48 {
            width: 48%;
        }

        .w-100 {
            width: 100%;
        }

        .float-left {
            float: left;
        }

        .float-right {
            float: right;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* COLORES */
        .text-primary {
            color: #755000;
        }

        .bg-primary {
            background-color: #755000;
            color: white;
        }

        /* HEADER */
        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2.5cm;
            padding: 30px 40px 10px 40px;
            background-color: #fff;
            border-bottom: 2px solid #d3a611;
        }

        .company-logo {
            max-width: 180px;
            max-height: 60px;
        }

        .invoice-title {
            font-size: 24px;
            color: #755000;
            text-align: right;
            line-height: 1;
        }

        .invoice-subtitle {
            font-size: 12px;
            color: #6b7280;
            text-align: right;
            margin-top: 5px;
        }

        /* BODY */
        .container {
            padding: 0 40px;
        }

        .meta-section {
            margin-bottom: 20px;
            margin-top: 20px;
        }

        .box-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .client-name {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .meta-data {
            font-size: 11px;
            line-height: 1.4;
        }

        /* TABLA */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            text-align: left;
            padding: 8px 5px;
            background-color: #755000;
            color: #fff;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .items-table td {
            padding: 8px 5px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* TOTALES */
        .totals-section {
            width: 40%;
            float: right;
            margin-bottom: 30px;
        }

        .total-row {
            padding: 4px 0;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .total-row.final {
            border-top: 2px solid #755000;
            margin-top: 15px;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #755000;
        }

        /* FOOTER */
        footer {
            position: fixed;
            bottom: 0cm;
            left: 0cm;
            right: 0cm;
            height: 1.5cm;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            line-height: 1.5cm;
            font-size: 9px;
            color: #9ca3af;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            float: right;
            margin-left: 10px;
            background: #e5e7eb;
            color: #374151;
        }
    </style>
</head>

<body>

    <header>
        <div class="clearfix">
            <div class="float-left" style="width: 50%;">
                @if($companyLogo)
                <img src="{{ $companyLogo }}" class="company-logo" alt="Logo">
                @else
                <h1 class="text-primary" style="margin:0;">{{ $company?->name }}</h1>
                @endif
                <div style="font-size: 9px; color: #6b7280; margin-top: 5px;">
                    {{ $company?->address }} <br>
                    {{ $company?->phone }} | {{ $company?->email }}
                </div>
            </div>

            <div class="float-right" style="width: 40%; text-align: right;">
                <div class="invoice-title">PRE-FACTURA</div>
                <div class="invoice-subtitle"># {{ $preInvoice->number }}</div>
                <div class="invoice-subtitle">Fecha: {{ $preInvoice->issue_date->format('d/m/Y') }}</div>
                <div class="invoice-subtitle">Vence: {{ $preInvoice->due_date?->format('d/m/Y') }}</div>

                <div style="margin-top: 5px;">
                    <span class="status-badge">
                        {{ strtoupper($preInvoice->status) }}
                    </span>
                </div>
            </div>
        </div>
    </header>

    <footer>
        {{ $company?->name }} - PRE-FACTURA / NO VALIDO COMO FACTURA FISCAL | Generado el {{ now()->format('d/m/Y H:i') }}
    </footer>

    <div class="container">

        <div class="meta-section clearfix">
            <div class="float-left" style="width: 48%; padding-right: 15px; margin-top:20px">
                <div class="box-title">Cliente</div>
                <div class="client-name">{{ $preInvoice->customer?->name }}</div>
                <div class="meta-data">
                    @if($preInvoice->customer?->tax_id) RNC: {{ $preInvoice->customer->tax_id }}<br> @endif
                    {{ $preInvoice->customer?->billing_address }}
                </div>
            </div>

            <div class="float-right" style="width: 48%; padding-left: 15px;  margin-top:20px">
                @if($preInvoice->shippingOrder)
                <div class="box-title">Referencia Orden de Envío</div>
                <div class="meta-data">
                    <span class="font-bold">Orden:</span> {{ $preInvoice->shippingOrder->order_number }}<br>
                    <span class="font-bold">Ruta:</span> {{ $preInvoice->shippingOrder->originPort?->code }} - {{ $preInvoice->shippingOrder->destinationPort?->code }}<br>
                </div>
                @endif
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Descripción</th>
                    <th style="width: 10%; text-align: right;">Cant.</th>
                    <th style="width: 15%; text-align: right;">Precio</th>
                    <th style="width: 15%; text-align: right;">ITBIS</th>
                    <th style="width: 15%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($preInvoice->lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="font-bold">{{ $line->code }}</div>
                        <div style="color: #64748b; font-size: 9px;">{{ $line->description }}</div>
                    </td>
                    <td class="text-right">{{ number_format($line->qty, 2) }}</td>
                    <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($line->tax_amount, 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($line->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="clearfix">
            <div class="totals-section">
                <div class="clearfix total-row">
                    <span class="float-left">Subtotal:</span>
                    <span class="float-right">{{ $preInvoice->currency_code }} {{ number_format($preInvoice->subtotal_amount, 2) }}</span>
                </div>

                @if($preInvoice->tax_amount > 0)
                <div class="clearfix total-row">
                    <span class="float-left">Impuestos:</span>
                    <span class="float-right">{{ $preInvoice->currency_code }} {{ number_format($preInvoice->tax_amount, 2) }}</span>
                </div>
                @endif

                <div class="clearfix total-row final">
                    <span class="float-left">TOTAL:</span>
                    <span class="float-right">{{ $preInvoice->currency_code }} {{ number_format($preInvoice->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if($preInvoice->notes)
        <div style="margin-top: 20px; padding: 10px; background: #f9fafb; border: 1px solid #e5e7eb;">
            <div class="box-title">Notas</div>
            <div style="font-size: 10px;">{{ $preInvoice->notes }}</div>
        </div>
        @endif

    </div>
</body>

</html>