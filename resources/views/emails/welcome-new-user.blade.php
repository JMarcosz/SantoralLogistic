<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido - {{ config('app.name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f7;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
        }

        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .credentials-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }

        .credentials-box h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .credential-item {
            display: flex;
            align-items: center;
            margin: 12px 0;
            font-size: 15px;
        }

        .credential-icon {
            width: 20px;
            margin-right: 10px;
            color: #667eea;
        }

        .credential-label {
            font-weight: 600;
            color: #555;
            margin-right: 8px;
        }

        .credential-value {
            color: #333;
            font-family: 'Courier New', monospace;
            background-color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }

        .cta-button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }

        .cta-button:hover {
            transform: translateY(-2px);
        }

        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .warning-icon {
            color: #ff9800;
            margin-right: 8px;
        }

        .warning p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }

        .footer p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 14px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            @if($company && $company['logo_url'])
            <img src="{{ $company['logo_url'] }}" alt="{{ $company['name'] }}" class="logo">
            @endif
            <h1>¡Bienvenido!</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p class="greeting">Hola <strong>{{ $user->name }}</strong>,</p>

            <p class="message">
                Tu cuenta en <strong>{{ config('app.name') }}</strong> ha sido creada exitosamente.
                A continuación encontrarás tus credenciales de acceso al sistema.
            </p>

            <!-- Credentials Box -->
            <div class="credentials-box">
                <h3>📋 Credenciales de Acceso</h3>

                <div class="credential-item">
                    <span class="credential-icon">📧</span>
                    <span class="credential-label">Email:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>

                <div class="credential-item">
                    <span class="credential-icon">🔑</span>
                    <span class="credential-label">Contraseña:</span>
                    <span class="credential-value">{{ $temporaryPassword }}</span>
                </div>
            </div>

            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ url('/login') }}" class="cta-button">
                    Iniciar Sesión
                </a>
            </div>

            <!-- Warning -->
            <div class="warning">
                <p>
                    <span class="warning-icon">⚠️</span>
                    <strong>Importante:</strong> Por seguridad, te recomendamos cambiar tu contraseña después de iniciar sesión por primera vez.
                </p>
            </div>

            <p class="message" style="margin-top: 30px;">
                Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
            @if($company && $company['email'])
            <p>
                <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>
            </p>
            @endif
        </div>
    </div>
</body>

</html>