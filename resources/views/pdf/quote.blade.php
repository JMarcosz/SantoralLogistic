<!DOCTYPE html>
<html lang="{{ $lang }}">

<head>
    <meta charset="UTF-8">
    <title>{{ __('Quote') }} {{ $quote->quote_number }}</title>
    <style>
        @page {
            margin: 0.5cm;
        }

        body {
            font-family: sans-serif;
            font-size: 10px;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* Header */
        .header-table td {
            vertical-align: top;
        }

        .company-logo {
            max-height: 60px;
            max-width: 200px;
        }

        .company-info {
            font-size: 9px;
            line-height: 1.2;
            margin-left: 10px;
        }

        .doc-title {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            color: #666;
            text-transform: uppercase;
        }

        .doc-number {
            font-size: 14px;
            font-weight: bold;
            text-align: right;
        }

        /* Boxes */
        .box {
            border: 1px solid #000;
            margin-bottom: -1px;
        }

        .box-header {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 2px 5px;
            border-bottom: 1px solid #000;
            font-size: 9px;
            text-transform: uppercase;
        }

        .box-content {
            padding: 5px;
            min-height: 40px;
        }

        /* Grid Layout */
        .row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .col {
            display: table-cell;
            border: 1px solid #000;
            vertical-align: top;
        }

        .col-header {
            font-weight: bold;
            padding: 2px 5px;
            border-bottom: 1px solid #000;
            background: #fff;
            font-size: 8px;
            text-transform: uppercase;
        }

        /* Specific Tables */
        .info-table td {
            border: 1px solid #000;
            padding: 2px 4px;
        }

        .info-label {
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            margin-top: 10px;
            border: 1px solid #000;
        }

        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Breakdown Table for Detailed Mode */
        .breakdown-table {
            width: 100%;
            margin-bottom: 15px;
            border: 1px solid #000;
        }

        .breakdown-table th {
            border: 1px solid #000;
            background: #fff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 4px;
            text-align: center;
        }

        .breakdown-table td {
            border: 1px solid #000;
            font-size: 9px;
            padding: 2px 4px;
            text-align: center;
        }

        /* Totals */
        .totals-table {
            width: 40%;
            float: right;
            margin-top: 5px;
            border: 1px solid #000;
        }

        .totals-table td {
            padding: 3px 5px;
            border: 1px solid #000;
        }

        .total-l {
            font-weight: bold;
            text-align: right;
        }

        .total-v {
            text-align: right;
        }

        /* Footer */
        .footer-section {
            border: 1px solid #000;
            margin-top: 10px;
            padding: 0;
        }

        .footer-title {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 2px 5px;
            border-bottom: 1px solid #000;
            font-size: 9px;
        }

        .footer-content {
            padding: 5px;
            min-height: 20px;
            font-size: 9px;
        }

        .page-num {
            text-align: right;
            font-size: 8px;
            margin-top: 5px;
        }

        /* Utility */
        .no-border {
            border: none !important;
        }

        .w-50 {
            width: 50%;
        }

        .w-100 {
            width: 100%;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <table class="header-table" style="margin-bottom: 10px;">
        <tr>
            <td width="60%">
                <table>
                    <tr>
                        @if(!empty($companyLogo))
                        <td width="1"><img src="{{ $companyLogo }}" class="company-logo"></td>
                        @endif
                        <td style="vertical-align: middle;">
                            @php
                            $marginLeft = !empty($companyLogo) ? '10px' : '0';
                            @endphp
                            <div class="company-info" style="margin-left: {{ $marginLeft }};">
                                <span style="font-size: 14px; font-weight: bold;">{{ $company?->name }}</span><br>
                                {{ $company?->address }}<br>
                                {{ $company?->phone }} | {{ $company?->email }}
                                @if($company?->rnc)<br>RNC: {{ $company->rnc }}@endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="40%" style="text-align: right;">
                <div class="doc-title">{{ __('Quote') }}</div>
                <div class="doc-number">{{ $quote->quote_number }}</div>
                <div style="font-size: 9px; margin-top: 5px;">
                    {{ __('Page') }} 1 of 1
                </div>
            </td>
        </tr>
    </table>

    <!-- INFO BLOCKS -->
    <table style="margin-bottom: 10px; border: 1px solid #000;">
        <tr>
            <!-- Left Info Block: Customer -->
            <td width="50%" style="padding: 0; border-right: 1px solid #000; vertical-align: top;">
                <div class="box-header">{{ __('Customer') }}</div>
                <div class="box-content">
                    <span class="bold">{{ $quote->customer?->name }}</span><br>
                    @if($quote->customer?->tax_id) RNC: {{ $quote->customer->tax_id }}<br> @endif
                    {!! nl2br(e($quote->customer?->billing_address)) !!}<br>
                    @if($quote->contact) Attn: {{ $quote->contact->name }} @endif
                </div>
            </td>

            <!-- Right Info Block: Additional Info -->
            <td width="50%" style="padding: 0; vertical-align: top;">
                <div class="box-header">{{ __('Additional Information') }}</div>
                <table class="info-table" style="border: none;">
                    <tr>
                        <td class="info-label no-border">{{ __('Date') }}:</td>
                        <td class="no-border">{{ $quote->created_at->format('d/m/Y') }}</td>
                        <td class="info-label no-border">{{ __('Expiration Date') }}:</td>
                        <td class="no-border">{{ \Carbon\Carbon::parse($quote->valid_until)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label no-border">{{ __('Origin') }}:</td>
                        <td class="no-border" colspan="3">{{ $quote->originPort?->name }}, {{ $quote->originPort?->country }} ({{ $quote->originPort?->code }})</td>
                    </tr>
                    <tr>
                        <td class="info-label no-border">{{ __('Destination') }}:</td>
                        <td class="no-border" colspan="3">{{ $quote->destinationPort?->name }}, {{ $quote->destinationPort?->country }} ({{ $quote->destinationPort?->code }})</td>
                    </tr>
                    <tr>
                        <td class="info-label no-border">{{ __('Payment Terms') }}:</td>
                        <td class="no-border">{{ $quote->paymentTerms?->name }}</td>
                        <td class="info-label no-border">{{ __('Service Type') }}:</td>
                        <td class="no-border">{{ $quote->serviceType?->name }}</td>
                    </tr>
                    <tr>
                        <td class="info-label no-border">{{ __('Incoterms') }}:</td>
                        <td class="no-border">{{ $quote->incoterms }}</td>
                        <td class="info-label no-border">{{ __('Transit Days') }}:</td>
                        <td class="no-border">{{ $quote->transit_days }}</td>
                    </tr>
                    <tr>
                        <td class="info-label no-border">{{ __('Sales Rep') }}:</td>
                        <td class="no-border" colspan="3">{{ $quote->salesRep?->name ?? $quote->createdBy?->name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- SHIPPER / CONSIGNEE (Only if requested or detailed mode, otherwise maybe optional? Reference image has them) -->
    <table style="margin-bottom: 10px; border: 1px solid #000;">
        <tr>
            <td width="50%" style="padding: 0; border-right: 1px solid #000; vertical-align: top;">
                <div class="box-header">{{ __('Shipper') }}</div>
                <div class="box-content">
                    @if($quote->shipper)
                    <span class="bold">{{ $quote->shipper->name }}</span><br>
                    {!! nl2br(e($quote->shipper->address)) !!}
                    @else
                    -
                    @endif
                </div>
            </td>
            <td width="50%" style="padding: 0; vertical-align: top;">
                <div class="box-header">{{ __('Consignee') }}</div>
                <div class="box-content">
                    @if($quote->consignee)
                    <span class="bold">{{ $quote->consignee->name }}</span><br>
                    {!! nl2br(e($quote->consignee->address)) !!}
                    @else
                    -
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- DETAILED BREAKDOWN (Pieces/Dimensions) - Only for Detailed Mode -->
    @if($mode === 'detailed')
    <table class="breakdown-table">
        <thead>
            <tr>
                <th>{{ __('Pieces') }}</th>
                <th>{{ __('Dimensions') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('Weight') }}</th>
                <th>{{ __('Volume') }}</th>
                <th>{{ __('Chargeable Weight') }}</th>
            </tr>
        </thead>
        <tbody>
            <!-- Assuming we have items or using totals if items not defined -->
            <!-- If no specific items, show totals -->
            <tr>
                <td>{{ number_format($quote->total_pieces) }}</td>
                <td>-</td>
                <td>GENERAL CARGO</td>
                <td>{{ number_format($quote->total_weight_kg, 2) }} kg</td>
                <td>{{ number_format($quote->total_volume_cbm, 3) }} m3</td>
                <td>{{ number_format($quote->chargeable_weight_kg, 2) }} kg</td>
            </tr>
        </tbody>
        <tfoot style="background: #f9f9f9; font-weight: bold;">
            <tr>
                <td style="text-align: center;">{{ __('Total') }}: {{ number_format($quote->total_pieces) }}</td>
                <td></td>
                <td></td>
                <td>{{ number_format($quote->total_weight_kg, 2) }} kg</td>
                <td>{{ number_format($quote->total_volume_cbm, 3) }} m3</td>
                <td>{{ number_format($quote->chargeable_weight_kg, 2) }} kg</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- ITEMS -->
    <table class="items-table">
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th width="10%">{{ __('Qty') }}</th>
                <th width="12%">{{ __('Unit Price') }}</th>
                <th width="15%">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->lines as $line)
            <tr>
                <td>
                    <span class="bold">{{ $line->productService?->name }}</span>
                    @if($line->description)<br><span style="color: #555; font-size: 8px;">{{ $line->description }}</span>@endif
                </td>
                <td class="text-center">{{ number_format($line->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
                <td class="text-right">{{ number_format($line->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TOTALS -->
    <div style="width: 100%; overflow: hidden;">
        <table class="totals-table">
            <tr>
                <td class="total-l">{{ __('Subtotal') }}:</td>
                <td class="total-v" width="40%">{{ $quote->currency?->symbol }} {{ number_format($quote->subtotal, 2) }}</td>
            </tr>
            @if($quote->tax_amount > 0)
            <tr>
                <td class="total-l">{{ __('Tax') }}:</td>
                <td class="total-v">{{ $quote->currency?->symbol }} {{ number_format($quote->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="total-l" style="background-color: #f0f0f0;">{{ __('Total Amount') }} ({{ $quote->currency?->code }}):</td>
                <td class="total-v" style="background-color: #f0f0f0; font-weight: bold;">
                    {{ $quote->currency?->symbol }} {{ number_format($quote->total_amount, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <!-- COMMENTS -->
    <div class="footer-section">
        <div class="footer-title">{{ __('Comments') }}</div>
        <div class="footer-content">
            {{ $quote->notes }}
        </div>
    </div>

    <!-- TERMS -->
    <div class="footer-section">
        <div class="footer-title">{{ __('Terms and Conditions') }}</div>
        <div class="footer-content" style="font-size: 8px;">
            @if($paymentTermsText)
            <strong>{{ __('Payment Terms') }}:</strong><br>
            {!! nl2br(e($paymentTermsText)) !!}
            <br><br>
            @endif

            @if($footerTermsText)
            <strong>{{ __('Additional Terms') }}:</strong><br>
            {!! nl2br(e($footerTermsText)) !!}
            @endif
        </div>
    </div>

</body>

</html>