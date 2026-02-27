<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Fiscal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }

        .invoice-details {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }

        .invoice-details table {
            width: 100%;
        }

        .invoice-details td {
            padding: 8px 0;
        }

        .invoice-details .label {
            color: #6b7280;
            font-weight: 600;
        }

        .invoice-details .value {
            text-align: right;
            font-family: monospace;
        }

        .ncf-box {
            background: #dbeafe;
            border: 2px solid #1e40af;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .ncf-label {
            color: #1e40af;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .ncf-value {
            color: #1e40af;
            font-size: 20px;
            font-weight: bold;
            font-family: monospace;
            margin-top: 5px;
        }

        .custom-message {
            background: #f3f4f6;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 20px 0;
            font-style: italic;
        }

        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e7eb;
            border-top: none;
            font-size: 12px;
            color: #6b7280;
        }

        .button {
            display: inline-block;
            background: #1e40af;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }

        .attachment-notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📄 Factura Fiscal</h1>
        <p style="margin: 10px 0 0 0; opacity: 0.9;">MAED LOGISTIC PLATFORM</p>
    </div>

    <div class="content">
        <p>Estimado/a <strong>{{ $invoice->customer->fiscal_name ?? $invoice->customer->name }}</strong>,</p>

        <p>Adjunto encontrará la factura fiscal correspondiente a los servicios prestados.</p>

        <div class="ncf-box">
            <div class="ncf-label">Número de Comprobante Fiscal (NCF)</div>
            <div class="ncf-value">{{ $invoice->ncf }}</div>
        </div>

        <div class="invoice-details">
            <table>
                <tr>
                    <td class="label">Número de Factura:</td>
                    <td class="value">{{ $invoice->number }}</td>
                </tr>
                <tr>
                    <td class="label">Tipo de Comprobante:</td>
                    <td class="value">{{ $invoice->ncf_type }}</td>
                </tr>
                <tr>
                    <td class="label">Fecha de Emisión:</td>
                    <td class="value">{{ $invoice->issue_date->format('d/m/Y') }}</td>
                </tr>
                @if($invoice->due_date)
                <tr>
                    <td class="label">Fecha de Vencimiento:</td>
                    <td class="value">{{ $invoice->due_date->format('d/m/Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Moneda:</td>
                    <td class="value">{{ $invoice->currency_code }}</td>
                </tr>
                <tr>
                    <td class="label" style="font-size: 16px; padding-top: 10px;">Total a Pagar:</td>
                    <td class="value" style="font-size: 18px; color: #1e40af; font-weight: bold; padding-top: 10px;">
                        {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}
                    </td>
                </tr>
            </table>
        </div>

        @if($customMessage)
        <div class="custom-message">
            <strong>Mensaje adicional:</strong><br>
            {{ $customMessage }}
        </div>
        @endif

        <div class="attachment-notice">
            <strong>📎 Archivo Adjunto</strong><br>
            El PDF de la factura fiscal se encuentra adjunto a este correo.
        </div>

        <p>Si tiene alguna pregunta sobre esta factura, no dude en contactarnos.</p>

        <p style="margin-top: 30px;">
            Atentamente,<br>
            <strong>MAED LOGISTIC PLATFORM</strong>
        </p>
    </div>

    <div class="footer">
        <p style="margin: 0;">
            Este es un correo electrónico automático. Por favor no responda a este mensaje.<br>
            Para consultas, contacte a: info@maedlogistic.com | 809-555-1234
        </p>
        <p style="margin: 10px 0 0 0; font-size: 10px;">
            © {{ date('Y') }} MAED LOGISTIC PLATFORM. Todos los derechos reservados.
        </p>
    </div>
</body>

</html>