<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Order {{ $order->order_number }}</title>
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

        .w-full {
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

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* COLORES CORPORATIVOS - Verde para Shipping Orders */
        .text-primary {
            color: #047857;
        }

        .bg-primary {
            background-color: #047857;
            color: white;
        }

        .bg-gray-light {
            background-color: #f3f4f6;
        }

        .border-bottom {
            border-bottom: 1px solid #e5e7eb;
        }

        /* HEADER (Posición Fija para que salga en todas las páginas) */
        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 2.5cm;
            padding: 30px 40px 10px 40px;
            background-color: #fff;
            border-bottom: 2px solid #047857;
        }

        .company-logo {
            max-width: 180px;
            max-height: 60px;
        }

        .invoice-title {
            font-size: 24px;
            color: #047857;
            text-align: right;
            line-height: 1;
        }

        .invoice-subtitle {
            font-size: 12px;
            color: #6b7280;
            text-align: right;
            margin-top: 5px;
        }

        /* CONTENIDO PRINCIPAL */
        .container {
            padding: 0 40px;
        }

        /* SECCIÓN CLIENTE Y DETALLES */
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

        /* TARJETA DE RUTA (LANE) - Diseño Moderno */
        .lane-card {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .route-visual {
            font-size: 16px;
            font-weight: bold;
            color: #047857;
            margin-bottom: 5px;
        }

        .route-sub {
            font-size: 10px;
            color: #64748b;
        }

        /* CAJAS DE MÉTRICAS (PESO/VOLUMEN) */
        .metrics-container {
            margin-top: 8px;
            border-top: 1px dashed #86efac;
            padding-top: 8px;
        }

        .metric-box {
            float: left;
            width: 33.33%;
            text-align: center;
            border-right: 1px solid #e2e8f0;
        }

        .metric-box:last-child {
            border-right: none;
        }

        .metric-value {
            font-weight: bold;
            color: #0f172a;
            font-size: 12px;
        }

        .metric-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            margin-top: 2px;
        }

        /* FECHAS DE TRACKING */
        .dates-grid {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .date-box {
            float: left;
            width: 25%;
            text-align: center;
            padding: 5px;
        }

        .date-value {
            font-size: 11px;
            font-weight: bold;
            color: #047857;
        }

        .date-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        /* TOTALES */
        .totals-section {
            background-color: #ecfdf5;
            border: 1px solid #6ee7b7;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #047857;
        }

        .total-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }

        /* TÉRMINOS Y CONDICIONES (Compacto) */
        .terms-wrapper {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .terms-title {
            font-size: 9px;
            font-weight: bold;
            color: #047857;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .terms-text {
            font-size: 8px;
            color: #4b5563;
            text-align: justify;
            line-height: 1.3;
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

        /* STATUS BADGE */
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            float: right;
            margin-left: 10px;
        }

        .status-draft {
            background: #e5e7eb;
            color: #374151;
        }

        .status-booked {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-in_transit {
            background: #fef3c7;
            color: #d97706;
        }

        .status-arrived {
            background: #cffafe;
            color: #0891b2;
        }

        .status-delivered {
            background: #dcfce7;
            color: #16a34a;
        }

        .status-closed {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        /* QUOTE REFERENCE */
        .quote-ref {
            background-color: #f0f9ff;
            border: 1px solid #7dd3fc;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #0369a1;
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
                <div class="invoice-title">SHIPPING ORDER</div>
                <div class="invoice-subtitle"># {{ $order->order_number }}</div>
                <div class="invoice-subtitle">Fecha: {{ $order->created_at->format('d/m/Y') }}</div>

                <div style="margin-top: 5px;">
                    <span class="status-badge status-{{ $order->status->value }}">
                        {{ $order->status->label() }}
                    </span>
                </div>
            </div>
        </div>
    </header>

    <footer>
        {{ $company?->name }} - RNC: {{ $company?->rnc }} | Generado el {{ now()->format('d/m/Y H:i') }}
    </footer>

    <div class="container">

        <div class="meta-section clearfix">
            <div class="float-left w-half" style="padding-right: 15px;">
                <div class="box-title">Cliente</div>
                <div class="client-name">{{ $order->customer?->name }}</div>
                <div class="meta-data">
                    @if($order->customer?->tax_id) RNC: {{ $order->customer->tax_id }}<br> @endif
                    {{ $order->customer?->billing_address }}<br>
                    @if($order->contact) Attn: {{ $order->contact->name }} @endif
                </div>
            </div>

            <div class="float-left w-half" style="padding-left: 15px;">
                <div class="box-title">Detalles del Servicio</div>
                <div class="meta-data">
                    <span class="font-bold">Modo:</span> {{ $order->transportMode?->name }}<br>
                    <span class="font-bold">Servicio:</span> {{ $order->serviceType?->name }}<br>
                    <span class="font-bold">Moneda:</span> {{ $order->currency?->code }} ({{ $order->currency?->symbol }})
                </div>
            </div>
        </div>

        <div class="lane-card">
            <div class="clearfix">
                <div class="float-left" style="width: 60%;">
                    <div class="route-sub">RUTA DE TRANSPORTE</div>
                    <div class="route-visual">
                        {{ $order->originPort?->code }} <span style="color:#94a3b8;">&#10140;</span> {{ $order->destinationPort?->code }}
                    </div>
                    <div style="font-size: 10px; color: #475569;">
                        {{ $order->originPort?->name }}, {{ $order->originPort?->country }}
                        &mdash;
                        {{ $order->destinationPort?->name }}, {{ $order->destinationPort?->country }}
                    </div>
                </div>

                <div class="float-right text-right" style="width: 35%;">
                    <div class="route-sub">TIPO DE SERVICIO</div>
                    <div style="font-weight: bold; font-size: 11px;">{{ $order->serviceType?->name }}</div>
                </div>
            </div>

            @if($order->total_pieces || $order->total_weight_kg || $order->total_volume_cbm)
            <div class="metrics-container clearfix">
                <div class="metric-box">
                    <div class="metric-value">{{ number_format($order->total_pieces ?? 0) }}</div>
                    <div class="metric-label">Piezas</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">{{ number_format($order->total_weight_kg ?? 0, 2) }} kg</div>
                    <div class="metric-label">Peso Bruto</div>
                </div>
                <div class="metric-box">
                    <div class="metric-value">{{ number_format($order->total_volume_cbm ?? 0, 3) }} m³</div>
                    <div class="metric-label">Volumen</div>
                </div>
            </div>
            @endif
        </div>

        <!-- Dates Section -->
        <div class="dates-grid clearfix">
            <div class="date-box">
                <div class="date-label">Salida Planificada</div>
                <div class="date-value">
                    {{ $order->planned_departure_at ? $order->planned_departure_at->format('d/m/Y H:i') : '-' }}
                </div>
            </div>
            <div class="date-box">
                <div class="date-label">Salida Real</div>
                <div class="date-value">
                    {{ $order->actual_departure_at ? $order->actual_departure_at->format('d/m/Y H:i') : '-' }}
                </div>
            </div>
            <div class="date-box">
                <div class="date-label">Llegada Planificada</div>
                <div class="date-value">
                    {{ $order->planned_arrival_at ? $order->planned_arrival_at->format('d/m/Y H:i') : '-' }}
                </div>
            </div>
            <div class="date-box">
                <div class="date-label">Llegada Real</div>
                <div class="date-value">
                    {{ $order->actual_arrival_at ? $order->actual_arrival_at->format('d/m/Y H:i') : '-' }}
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="totals-section">
            <div class="total-label">Total</div>
            <div class="total-amount">
                {{ $order->currency?->symbol }}{{ number_format($order->total_amount, 2) }} {{ $order->currency?->code }}
            </div>
        </div>

        <!-- Quote Reference -->
        @if($order->quote)
        <div class="quote-ref">
            <strong>📄 Origen de Cotización:</strong> {{ $order->quote->quote_number }}
        </div>
        @endif

        <!-- Notes -->
        @if($order->notes)
        <div class="terms-wrapper">
            <div class="terms-title">Notas</div>
            <div class="terms-text" style="font-style: italic;">
                {{ $order->notes }}
            </div>
        </div>
        @endif

        <!-- Terms & Conditions -->
        @if(!empty($footerTermsText))
        <div class="terms-wrapper">
            <div class="terms-title">Términos y Condiciones</div>
            <div class="terms-text">{!! nl2br(e($footerTermsText)) !!}</div>
        </div>
        @endif

    </div>
</body>

</html>