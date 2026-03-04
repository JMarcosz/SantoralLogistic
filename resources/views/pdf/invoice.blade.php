<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Fiscal {{ $invoice->number }}</title>
    <style>
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

        /* UTILITIES */
        .w-48 {
            width: 48%;
        }

        .w-100 {
            width: 100%;
        }

        .w-half {
            width: 50%;
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

        .text-uppercase {
            text-transform: uppercase;
        }

        .font-bold {
            font-weight: bold;
        }

        .font-mono {
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* COLORS */
        .text-primary {
            color: #1e40af;
        }

        .bg-primary {
            background-color: #1e40af;
            color: white;
        }

        .bg-gray-light {
            background-color: #f3f4f6;
        }

        /* HEADER */
        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 3cm;
            padding: 30px 40px 10px 40px;
            background-color: #fff;
            border-bottom: 2px solid #1e40af;
        }

        .company-logo {
            max-width: 180px;
            max-height: 60px;
        }

        .invoice-title {
            font-size: 28px;
            color: #1e40af;
            text-align: right;
            line-height: 1;
        }

        .invoice-subtitle {
            font-size: 12px;
            color: #6b7280;
            text-align: right;
            margin-top: 5px;
        }

        .ncf-box {
            background-color: #dbeafe;
            border: 2px solid #1e40af;
            padding: 8px 12px;
            margin-top: 10px;
            border-radius: 4px;
        }

        .ncf-label {
            font-size: 9px;
            color: #1e40af;
            text-transform: uppercase;
            font-weight: bold;
        }

        .ncf-value {
            font-size: 16px;
            color: #1e40af;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
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

        /* CONTENT */
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
            padding-bottom: 2px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .client-name {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .meta-data {
            font-size: 11px;
            line-height: 1.4;
        }

        /* TABLE */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            text-align: left;
            padding: 8px 5px;
            background-color: #1e40af;
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

        /* TOTALS */
        .totals-section {
            width: 45%;
            float: right;
            margin-bottom: 30px;
        }

        .total-row {
            padding: 4px 0;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .total-row.final {
            border-top: 2px solid #1e40af;
            margin-top: 15px;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
        }

        /* STATUS BADGE */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-issued {
            background: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .cancellation-notice {
            background-color: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .cancellation-title {
            font-size: 12px;
            font-weight: bold;
            color: #991b1b;
            margin-bottom: 8px;
        }

        .cancellation-text {
            font-size: 10px;
            color: #7f1d1d;
            line-height: 1.4;
        }
    </style>
</head>

<body>

    <header>
        <div class="clearfix">
            <div class="float-left" style="width: 55%;">
                @if(isset($companyLogo))
                <img src="{{ $companyLogo }}" class="company-logo" alt="Logo" style="margin-bottom: 5px;">
                @elseif(isset($company))
                <h1 class="text-primary" style="margin:0; font-size: 18px;">{{ $company->name }}</h1>
                @else
                <h1 class="text-primary" style="margin:0; font-size: 18px;">MAED LOGISTIC PLATFORM</h1>
                @endif

                <div style="font-size: 9px; color: #6b7280; margin-top: 5px;">
                    @if(isset($company))
                    RNC: {{ $company->rnc }}<br>
                    {{ $company->address }}<br>
                    {{ $company->phone }} | {{ $company->email }}
                    @else
                    RNC: 123456789<br>
                    Ave. Winston Churchill, Santo Domingo<br>
                    809-555-1234 | info@maedlogistic.com
                    @endif
                </div>
            </div>

            <div class="float-right" style="width: 40%; text-align: right;">
                <div class="invoice-title">FACTURA FISCAL</div>
                <div class="invoice-subtitle"># {{ $invoice->number }}</div>
                <div class="invoice-subtitle">Fecha: {{ $invoice->issue_date->format('d/m/Y') }}</div>

                <div class="ncf-box">
                    <!-- <div class="ncf-label">NCF ({{ $invoice->ncf_type }})</div> -->
                    <div class="ncf-value">{{ $invoice->ncf }}</div>
                </div>

                <div style="margin-top: 8px;">
                    <span class="status-badge status-{{ $invoice->status }}">
                        {{ $invoice->status === 'issued' ? 'EMITIDA' : 'CANCELADA' }}
                    </span>
                </div>
            </div>
        </div>
    </header>

    <footer>
        Café Santoral Logistic - RNC: 123456789 | Generado el {{ now()->format('d/m/Y H:i') }}
    </footer>

    <div class="container">

        @if($invoice->status === 'cancelled')
        <div class="cancellation-notice">
            <div class="cancellation-title">⚠ FACTURA CANCELADA</div>
            <div class="cancellation-text">
                <strong>Fecha de cancelación:</strong> {{ $invoice->cancelled_at->format('d/m/Y H:i') }}<br>
                <strong>Motivo:</strong> {{ $invoice->cancellation_reason }}
            </div>
        </div>
        @endif

        <div class="meta-section clearfix">
            <div class="float-left w-half" style="padding-right: 15px;">
                <div class="box-title">Datos del Cliente</div>
                <div class="client-name">{{ $invoice->customer->fiscal_name ?? $invoice->customer->name }}</div>
                <div class="meta-data">
                    @if($invoice->customer->tax_id_type && $invoice->customer->tax_id)
                    <strong>{{ $invoice->customer->tax_id_type }}:</strong> {{ $invoice->customer->tax_id }}<br>
                    @endif
                    @if($invoice->customer->billing_address)
                    {{ $invoice->customer->billing_address }}<br>
                    @endif
                    @if($invoice->customer->country)
                    {{ $invoice->customer->country }}<br>
                    @endif
                </div>
            </div>

            <div class="float-left w-half" style="padding-left: 15px;">
                <div class="box-title">Detalles Fiscales</div>
                <div class="meta-data">
                    <strong>Tipo de Comprobante:</strong> {{ $invoice->ncf_type }}<br>
                    <strong>Moneda:</strong> {{ $invoice->currency_code }}<br>
                    <strong>Fecha de Vencimiento:</strong> {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : 'N/A' }}<br>
                    @if($invoice->preInvoice)
                    <strong>Pre-Factura:</strong> {{ $invoice->preInvoice->number }}<br>
                    @endif
                    @if($invoice->shippingOrder)
                    <strong>Orden de Envío:</strong> {{ $invoice->shippingOrder->so_number }}<br>
                    @endif
                </div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 12%;">Código</th>
                    <th style="width: 38%;">Descripción</th>
                    <th style="width: 10%; text-align: right;">Cant.</th>
                    <th style="width: 12%; text-align: right;">P. Unit</th>
                    <th style="width: 10%; text-align: right;">ITBIS</th>
                    <th style="width: 8%; text-align: right;">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $line->code }}</td>
                    <td>{{ $line->description }}</td>
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
                    <span class="float-right">{{ $invoice->currency_code }} {{ number_format($invoice->subtotal_amount, 2) }}</span>
                </div>

                <div class="clearfix total-row">
                    <span class="float-left">ITBIS (18%):</span>
                    <span class="float-right">{{ $invoice->currency_code }} {{ number_format($invoice->tax_amount, 2) }}</span>
                </div>

                @if($invoice->exempt_amount > 0)
                <div class="clearfix total-row">
                    <span class="float-left">Monto Exento:</span>
                    <span class="float-right">{{ $invoice->currency_code }} {{ number_format($invoice->exempt_amount, 2) }}</span>
                </div>
                @endif

                @if($invoice->taxable_amount > 0)
                <div class="clearfix total-row">
                    <span class="float-left">Monto Gravado:</span>
                    <span class="float-right">{{ $invoice->currency_code }} {{ number_format($invoice->taxable_amount, 2) }}</span>
                </div>
                @endif

                <div class="clearfix total-row final">
                    <span class="float-left">TOTAL:</span>
                    <span class="float-right">{{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if($invoice->notes)
        <div class="clearfix" style="margin-top: 20px;">
            <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px;">
                <div style="font-size: 9px; font-weight: bold; color: #1e40af; margin-bottom: 4px; text-transform: uppercase;">
                    Notas
                </div>
                <div style="font-size: 9px; color: #4b5563; line-height: 1.3;">
                    {{ $invoice->notes }}
                </div>
            </div>
        </div>
        @endif

    </div>
</body>

</html>