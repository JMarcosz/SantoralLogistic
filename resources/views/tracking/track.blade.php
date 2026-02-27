@extends('layouts.public-tracking')

@section('title', 'Tracking ' . $order->order_number)

@php
$statusLabels = [
'draft' => 'Borrador',
'booked' => 'Reservado',
'in_transit' => 'En Tránsito',
'arrived' => 'Llegado',
'delivered' => 'Entregado',
'closed' => 'Cerrado',
'cancelled' => 'Cancelado',
];

$statusClass = 'status-' . $order->status->value;
$statusLabel = $statusLabels[$order->status->value] ?? $order->status->value;
@endphp

@section('content')
{{-- Order Header --}}
<div class="card">
    <div class="card-header">
        <div class="order-number">{{ $order->order_number }}</div>
        <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
    </div>

    @if($customerDisplayName)
    <p style="color: #a1a1aa; font-size: 0.875rem;">
        Cliente: <strong style="color: #fff;">{{ $customerDisplayName }}</strong>
    </p>
    @endif
</div>

{{-- Route --}}
<div class="card">
    <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
        Ruta
    </div>

    <div class="route-section">
        <div class="port">
            <div class="port-code">{{ $order->originPort?->code }}</div>
            <div class="port-name">{{ $order->originPort?->name }}</div>
            <div class="port-name" style="font-size: 0.75rem;">{{ $order->originPort?->country }}</div>
        </div>

        <div class="route-arrow">
            @php
            $modeIcon = match($order->transportMode?->code) {
            'AIR' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
            </svg>',
            'OCEAN' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" />
            </svg>',
            'GROUND' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
            </svg>',
            default => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
            </svg>',
            };
            @endphp
            {!! $modeIcon !!}
        </div>

        <div class="port">
            <div class="port-code">{{ $order->destinationPort?->code }}</div>
            <div class="port-name">{{ $order->destinationPort?->name }}</div>
            <div class="port-name" style="font-size: 0.75rem;">{{ $order->destinationPort?->country }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Modo de Transporte</div>
            <div class="info-value">{{ $order->transportMode?->name ?? '-' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Tipo de Servicio</div>
            <div class="info-value">{{ $order->serviceType?->name ?? '-' }}</div>
        </div>
    </div>
</div>

{{-- Dates --}}
<div class="card">
    <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
        Fechas
    </div>

    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Salida Prevista</div>
            <div class="info-value">
                {{ $order->planned_departure_at ? $order->planned_departure_at->format('d M Y') : '-' }}
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">Llegada Prevista</div>
            <div class="info-value">
                {{ $order->planned_arrival_at ? $order->planned_arrival_at->format('d M Y') : '-' }}
            </div>
        </div>
        @if($order->actual_departure_at)
        <div class="info-item">
            <div class="info-label">Salida Real</div>
            <div class="info-value" style="color: #22c55e;">
                {{ $order->actual_departure_at->format('d M Y, H:i') }}
            </div>
        </div>
        @endif
        @if($order->actual_arrival_at)
        <div class="info-item">
            <div class="info-label">Llegada Real</div>
            <div class="info-value" style="color: #22c55e;">
                {{ $order->actual_arrival_at->format('d M Y, H:i') }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Timeline --}}
<div class="card">
    <div class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        Historial de Eventos
    </div>

    @if($order->milestones && count($order->milestones) > 0)
    <div class="timeline">
        @foreach($order->milestones as $milestone)
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <div class="milestone-label">{{ $milestone->label }}</div>
                <div class="milestone-date">
                    {{ $milestone->happened_at->format('d M Y, H:i') }}
                </div>
                @if($milestone->location)
                <div class="milestone-location">
                    📍 {{ $milestone->location }}
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <p>No hay eventos de tracking registrados aún.</p>
    </div>
    @endif
</div>
@endsection