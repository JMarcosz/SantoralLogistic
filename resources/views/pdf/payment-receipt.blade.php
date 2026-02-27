<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago {{ $payment->payment_number }}</title>
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
        .w-half {
            width: 50%;
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

        .text-green {
            color: #059669;
        }

        .bg-primary {
            background-color: #1e40af;
            color: white;
        }

        .bg-success {
            background-color: #dcfce7;
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
            border-bottom: 2px solid #059669;
        }

        .company-logo {
            max-width: 180px;
            max-height: 60px;
        }


        .receipt-title {
            font-size: 24px;
            color: #059669;
            text-align: right;
            line-height: 1;
        }

        .receipt-subtitle {
            font-size: 12px;
            color: #6b7280;
            text-align: right;
            margin-top: 5px;
        }

        .receipt-badge {
            background-color: #dcfce7;
            border: 2px solid #059669;
            padding: 8px 15px;
            margin-top: 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .receipt-label {
            font-size: 9px;
            color: #059669;
            text-transform: uppercase;
            font-weight: bold;
        }

        .receipt-value {
            font-size: 14px;
            color: #059669;
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

        /* AMOUNT BOX */
        .amount-box {
            background-color: #f0fdf4;
            border: 2px solid #059669;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .amount-label {
            font-size: 12px;
            color: #059669;
            text-transform: uppercase;
            font-weight: bold;
        }

        .amount-value {
            font-size: 28px;
            color: #059669;
            font-weight: bold;
            font-family: 'DejaVu Sans Mono', monospace;
            margin-top: 5px;
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
            background-color: #059669;
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
            border-top: 2px solid #059669;
            margin-top: 15px;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #059669;
        }

        /* STATUS */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-posted {
            background: #dcfce7;
            color: #166534;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }

        .payment-detail {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 20px;
        }

        .detail-row {
            margin-bottom: 4px;
            font-size: 11px;
        }

        .detail-label {
            color: #6b7280;
            display: inline-block;
            width: 120px;
        }

        .detail-value {
            font-weight: bold;
            color: #111827;
        }
    </style>
</head>

<body>

    <header>
        <div class="clearfix">
            <div class="float-left" style="width: 55%;">
                @if($companyLogo)
                <img src="{{ $companyLogo }}" class="company-logo" alt="Logo">
                @else
                <h1 class="text-primary" style="margin:0; font-size: 18px;">{{ $company?->name ?? 'MAED LOGISTIC PLATFORM' }}</h1>
                @endif
                <div style="font-size: 9px; color: #6b7280; margin-top: 5px;">
                    @if($company?->tax_id)
                    RNC: {{ $company->tax_id }}<br>
                    @endif
                    @if($company?->address)
                    {{ $company->address }}<br>
                    @endif
                    @if($company?->phone)
                    {{ $company->phone }}
                    @endif
                    @if($company?->email)
                    | {{ $company->email }}
                    @endif
                </div>
            </div>

            <div class="float-right" style="width: 40%; text-align: right;">
                <div class="receipt-title">RECIBO DE PAGO</div>
                <div class="receipt-subtitle"># {{ $payment->payment_number }}</div>
                <div class="receipt-subtitle">Fecha: {{ $payment->payment_date->format('d/m/Y') }}</div>

                <div class="receipt-badge">
                    <div class="receipt-label">TOTAL RECIBIDO</div>
                    <div class="receipt-value">{{ $payment->currency_code }} {{ number_format($payment->amount, 2) }}</div>
                </div>
            </div>
        </div>
    </header>

    <footer>
        {{ $company?->company_name ?? 'MAED LOGISTIC PLATFORM' }} @if($company?->tax_id)- RNC: {{ $company->tax_id }}@endif | Generado el {{ now()->format('d/m/Y H:i') }}
    </footer>

    <div class="container">

        <div class="meta-section clearfix">
            <div class="float-left w-half" style="padding-right: 15px;">
                <div class="box-title">Datos del Cliente</div>
                <div class="client-name">{{ $payment->customer->fiscal_name ?? $payment->customer->name }}</div>
                <div class="meta-data">
                    @if($payment->customer->tax_id_type && $payment->customer->tax_id)
                    <strong>{{ $payment->customer->tax_id_type }}:</strong> {{ $payment->customer->tax_id }}<br>
                    @endif
                    @if($payment->customer->billing_address)
                    {{ $payment->customer->billing_address }}<br>
                    @endif
                    @if($payment->customer->phone)
                    Tel: {{ $payment->customer->phone }}<br>
                    @endif
                </div>
            </div>

            <div class="float-left w-half" style="padding-left: 15px;">
                <div class="box-title">Detalles del Pago</div>
                <div class="payment-detail">
                    <div class="detail-row">
                        <span class="detail-label">Método de Pago:</span>
                        <span class="detail-value">{{ $payment->paymentMethod?->name ?? 'N/A' }}</span>
                    </div>
                    @if($payment->reference)
                    <div class="detail-row">
                        <span class="detail-label">Referencia:</span>
                        <span class="detail-value font-mono">{{ $payment->reference }}</span>
                    </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Moneda:</span>
                        <span class="detail-value">{{ $payment->currency_code }}</span>
                    </div>
                    @if($payment->exchange_rate != 1)
                    <div class="detail-row">
                        <span class="detail-label">Tasa de Cambio:</span>
                        <span class="detail-value font-mono">{{ number_format($payment->exchange_rate, 4) }}</span>
                    </div>
                    @endif
                    <div class="detail-row">
                        <span class="detail-label">Estado:</span>
                        <span class="status-badge status-{{ $payment->status }}">
                            {{ $payment->status === 'posted' ? 'Contabilizado' : ($payment->status === 'draft' ? 'Borrador' : $payment->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if($payment->allocations->count() > 0)
        <div style="margin-top: 20px;">
            <div class="box-title">Facturas Afectadas</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">No. Factura</th>
                        <th style="width: 20%;">Fecha</th>
                        <th style="width: 20%; text-align: right;">Total Factura</th>
                        <th style="width: 20%; text-align: right;">Monto Abonado</th>
                        <th style="width: 15%; text-align: right;">Saldo Restante</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment->allocations as $allocation)
                    <tr>
                        <td class="font-mono">
                            {{ $allocation->invoice->number ?? 'N/A' }}
                            @if($allocation->invoice->ncf)
                            <br>
                            <span style="font-size: 8px; color: #6b7280;">NCF: {{ $allocation->invoice->ncf }}</span>
                            @endif
                        </td>
                        <td>{{ $allocation->invoice->issue_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="text-right">{{ number_format($allocation->invoice->total_amount ?? 0, 2) }}</td>
                        <td class="text-right font-bold text-green">{{ number_format($allocation->amount_applied, 2) }}</td>
                        <td class="text-right">{{ number_format($allocation->invoice->balance ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="clearfix">
            <div class="totals-section">
                @if($payment->isr_withholding_amount > 0)
                <div class="clearfix total-row">
                    <span class="float-left">Retención ISR:</span>
                    <span class="float-right">{{ $payment->currency_code }} {{ number_format($payment->isr_withholding_amount, 2) }}</span>
                </div>
                @endif

                @if($payment->itbis_withholding_amount > 0)
                <div class="clearfix total-row">
                    <span class="float-left">Retención ITBIS:</span>
                    <span class="float-right">{{ $payment->currency_code }} {{ number_format($payment->itbis_withholding_amount, 2) }}</span>
                </div>
                @endif

                @if($payment->isr_withholding_amount > 0 || $payment->itbis_withholding_amount > 0)
                <div class="clearfix total-row">
                    <span class="float-left">Total Retenciones:</span>
                    <span class="float-right">{{ $payment->currency_code }} {{ number_format($payment->isr_withholding_amount + $payment->itbis_withholding_amount, 2) }}</span>
                </div>
                @endif

                <div class="clearfix total-row final">
                    <span class="float-left">TOTAL RECIBIDO:</span>
                    <span class="float-right">{{ $payment->currency_code }} {{ number_format($payment->amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if($payment->notes)
        <div class="clearfix" style="margin-top: 20px;">
            <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px;">
                <div style="font-size: 9px; font-weight: bold; color: #059669; margin-bottom: 4px; text-transform: uppercase;">
                    Notas
                </div>
                <div style="font-size: 9px; color: #4b5563; line-height: 1.3;">
                    {{ $payment->notes }}
                </div>
            </div>
        </div>
        @endif

        <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af;">
            <p>Este recibo es un comprobante de pago. Conserve este documento para sus registros.</p>
        </div>

    </div>
</body>

</html>