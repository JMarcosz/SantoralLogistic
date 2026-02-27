/*Para probar usa: http://maedlogisticplatform.test/payments/1/pdf */

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Ingreso {{ $payment->id }}</title>
    <style>
        @page { margin: 0cm 0cm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #374151;
            margin-top: 3cm;
            margin-bottom: 2cm;
            background-color: #fff;
        }
        header {
            position: fixed;
            top: 0cm; left: 0cm; right: 0cm;
            height: 3cm;
            padding: 30px 40px 10px 40px;
            background-color: #fff;
            border-bottom: 2px solid #1e40af;
        }
        .company-logo { max-width: 180px; max-height: 60px; }
        .invoice-title {
            font-size: 24px; color: #1e40af;
            text-align: right; line-height: 1;
        }
        .invoice-subtitle {
            font-size: 12px; color: #6b7280;
            text-align: right; margin-top: 5px;
        }
        footer {
            position: fixed;
            bottom: 0cm; left: 0cm; right: 0cm;
            height: 1.5cm;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            line-height: 1.5cm;
            font-size: 9px; color: #9ca3af;
        }
        .container { padding: 0 40px; }
        .box-title {
            font-size: 10px; text-transform: uppercase;
            color: #6b7280; border-bottom: 1px solid #e5e7eb;
            margin-bottom: 5px; padding-bottom: 2px;
            font-weight: bold; letter-spacing: 0.5px;
        }
        .client-name { font-size: 14px; font-weight: bold; color: #111827; margin-bottom: 2px; margin-top:40px; }
        .meta-data { font-size: 11px; line-height: 1.4; }
        .items-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .items-table th {
            text-align: left; padding: 8px 5px;
            background-color: #1e40af; color: #fff;
            font-size: 9px; text-transform: uppercase;
            font-weight: bold;
        }
        .items-table td {
            padding: 8px 5px; border-bottom: 1px solid #e5e7eb;
            font-size: 11px; vertical-align: top;
        }
        .items-table tr:nth-child(even) { background-color: #f9fafb; }
    </style>
</head>
<body>
    <header>
        <div class="clearfix">
        <div class="float-left" style="width: 50%;">
            @if($company?->logo_path)
                <img src="{{ storage_path('app/public/' . $company->logo_path) }}" class="company-logo" alt="Logo">
            @else
                <h1 class="text-primary" style="margin:0;">{{ $company?->name }}</h1>
            @endif
            <div style="font-size: 9px; color: #6b7280; margin-top: 5px;">
                {{ $company?->address }} <br>
                {{ $company?->phone }} | {{ $company?->email }}
            </div>
        </div>

        
        <div class="invoice-title">Recibo de Ingreso</div>
        <div class="invoice-subtitle">Pago #{{ $payment->id }}</div>
    </header>

    <footer>
        Documento generado por Maed Logistic - {{ now()->format('d/m/Y H:i') }}
    </footer>

    <main class="container">
        <div class="meta-section">
            <div class="box-title">Datos del Cliente</div>
            <p class="client-name">{{ optional($payment->customer)->name ?? 'Cliente no asignado' }}</p>
            <p class="meta-data">RNC/Fiscal: {{ optional($payment->customer)->fiscal_name ?? 'N/A' }}</p>
        </div>

        <div class="meta-section">
            <div class="box-title">Detalle del Pago</div>
            <p class="meta-data"><strong>Método:</strong> {{ optional($payment->paymentMethod)->name ?? 'N/A' }}</p>
            <p class="meta-data"><strong>Referencia:</strong> {{ $payment->reference ?? 'N/A' }}</p>
            <p class="meta-data"><strong>Banco:</strong> {{ optional($payment->paymentMethod)->bank ?? 'N/A' }}</p>
            <p class="meta-data"><strong>Monto:</strong> {{ number_format($payment->amount, 2) }} {{ $payment->currency_code }}</p>
        </div>

        <div class="meta-section">
            <div class="box-title">Facturas Afectadas</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Monto Abonado</th>
                        <th>Saldo Restante</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payment->allocations as $allocation)
                        <tr>
                            <td>{{ optional($allocation->invoice)->number ?? 'N/A' }}</td>
                            <td>{{ number_format($allocation->amount_applied, 2) }}</td>
                            <td>
                                @if($allocation->invoice)
                                    {{ number_format($allocation->invoice->total_amount - $allocation->amount_applied, 2) }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;">No hay facturas asignadas a este pago</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>