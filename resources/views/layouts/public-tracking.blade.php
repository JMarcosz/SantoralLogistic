<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tracking') - {{ $company?->company_name ?? config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <style>
        :root {
            --primary: 142.1 76.2% 36.3%;
            --primary-foreground: 355.7 100% 97.3%;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #e5e5e5;
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 0.5rem;
        }

        .company-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
        }

        .card {
            background: rgba(30, 30, 40, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-draft {
            background: rgba(100, 116, 139, 0.2);
            color: #94a3b8;
            border: 1px solid rgba(100, 116, 139, 0.3);
        }

        .status-booked {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .status-in_transit {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-arrived {
            background: rgba(6, 182, 212, 0.2);
            color: #22d3ee;
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        .status-delivered {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-closed {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .route-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 0.75rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .port {
            text-align: center;
            min-width: 120px;
        }

        .port-code {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
        }

        .port-name {
            font-size: 0.875rem;
            color: #a1a1aa;
        }

        .route-arrow {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #22c55e;
        }

        .route-arrow svg {
            width: 24px;
            height: 24px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 0.5rem;
        }

        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #71717a;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 500;
            color: #fff;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #22c55e, rgba(34, 197, 94, 0.2));
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -2rem;
            top: 0.25rem;
            width: 16px;
            height: 16px;
            background: #22c55e;
            border: 3px solid #1a1a2e;
            border-radius: 50%;
        }

        .timeline-content {
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .milestone-label {
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.25rem;
        }

        .milestone-date {
            font-size: 0.875rem;
            color: #a1a1aa;
        }

        .milestone-location {
            font-size: 0.875rem;
            color: #71717a;
            margin-top: 0.25rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title svg {
            width: 20px;
            height: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #71717a;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #71717a;
        }

        @media (max-width: 640px) {
            .container {
                padding: 1rem;
            }

            .route-section {
                flex-direction: column;
            }

            .route-arrow {
                transform: rotate(90deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            @if($company?->logo_path)
            <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->company_name }}" class="logo">
            @endif
            <div class="company-name">{{ $company?->company_name ?? config('app.name') }}</div>
        </header>

        @yield('content')

        <footer class="footer">
            <p>© {{ date('Y') }} {{ $company?->company_name ?? config('app.name') }}. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>

</html>