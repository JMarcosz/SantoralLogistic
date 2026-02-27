<?php

namespace App\Enums;

/**
 * Standard milestone codes for Shipping Orders.
 * 
 * These are suggested codes but the system is flexible
 * to accept custom codes as well.
 */
enum MilestoneCode: string
{
    // Booking phase
    case Booked = 'BOOKED';
    case CarrierAssigned = 'CARRIER_ASSIGNED';
    case PickupScheduled = 'PICKUP_SCHEDULED';
    case PickedUp = 'PICKED_UP';

        // Origin phase
    case AtOriginWarehouse = 'AT_ORIGIN_WAREHOUSE';
    case DepartedOrigin = 'DEPARTED_ORIGIN';

        // Transit phase
    case InTransit = 'IN_TRANSIT';
    case Transshipment = 'TRANSSHIPMENT';
    case DelayReported = 'DELAY_REPORTED';

        // Destination phase
    case ArrivedDestination = 'ARRIVED_DESTINATION';
    case CustomsCleared = 'CUSTOMS_CLEARED';
    case CustomsHold = 'CUSTOMS_HOLD';
    case AtDestWarehouse = 'AT_DEST_WAREHOUSE';
    case OutForDelivery = 'OUT_FOR_DELIVERY';

        // Final phase
    case Delivered = 'DELIVERED';
    case DeliveryAttempt = 'DELIVERY_ATTEMPT';
    case ReturnedToSender = 'RETURNED_TO_SENDER';

        // Administrative
    case Cancelled = 'CANCELLED';
    case OnHold = 'ON_HOLD';
    case Released = 'RELEASED';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Reservado',
            self::CarrierAssigned => 'Transportista Asignado',
            self::PickupScheduled => 'Recogida Programada',
            self::PickedUp => 'Recogido',
            self::AtOriginWarehouse => 'En Almacén de Origen',
            self::DepartedOrigin => 'Salida de Origen',
            self::InTransit => 'En Tránsito',
            self::Transshipment => 'Transbordo',
            self::DelayReported => 'Retraso Reportado',
            self::ArrivedDestination => 'Llegada a Destino',
            self::CustomsCleared => 'Aduana Despachada',
            self::CustomsHold => 'Retenido en Aduana',
            self::AtDestWarehouse => 'En Almacén de Destino',
            self::OutForDelivery => 'En Camino a Entrega',
            self::Delivered => 'Entregado',
            self::DeliveryAttempt => 'Intento de Entrega',
            self::ReturnedToSender => 'Devuelto al Remitente',
            self::Cancelled => 'Cancelado',
            self::OnHold => 'En Espera',
            self::Released => 'Liberado',
        };
    }

    /**
     * Get icon name for frontend.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Booked => 'check-circle',
            self::CarrierAssigned => 'truck',
            self::PickupScheduled => 'calendar',
            self::PickedUp => 'package',
            self::AtOriginWarehouse => 'warehouse',
            self::DepartedOrigin => 'plane-takeoff',
            self::InTransit => 'route',
            self::Transshipment => 'arrow-left-right',
            self::DelayReported => 'alert-triangle',
            self::ArrivedDestination => 'plane-landing',
            self::CustomsCleared => 'shield-check',
            self::CustomsHold => 'shield-alert',
            self::AtDestWarehouse => 'warehouse',
            self::OutForDelivery => 'truck',
            self::Delivered => 'check',
            self::DeliveryAttempt => 'clock',
            self::ReturnedToSender => 'undo',
            self::Cancelled => 'x-circle',
            self::OnHold => 'pause-circle',
            self::Released => 'play-circle',
        };
    }

    /**
     * Get color for badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Booked, self::CarrierAssigned => 'blue',
            self::PickupScheduled, self::PickedUp => 'indigo',
            self::AtOriginWarehouse => 'violet',
            self::DepartedOrigin, self::InTransit => 'amber',
            self::Transshipment => 'orange',
            self::DelayReported, self::CustomsHold => 'red',
            self::ArrivedDestination, self::CustomsCleared => 'cyan',
            self::AtDestWarehouse, self::OutForDelivery => 'teal',
            self::Delivered => 'emerald',
            self::DeliveryAttempt => 'yellow',
            self::ReturnedToSender, self::Cancelled => 'red',
            self::OnHold => 'gray',
            self::Released => 'green',
        };
    }

    /**
     * Get all codes as options for selects.
     */
    public static function options(): array
    {
        return collect(self::cases())->map(fn($code) => [
            'value' => $code->value,
            'label' => $code->label(),
        ])->toArray();
    }
}
