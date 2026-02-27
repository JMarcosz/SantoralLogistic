<?php

namespace App\Services;

use App\Models\AirShipment;
use App\Models\OceanShipment;
use App\Models\ShippingOrder;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing modal-specific shipment details.
 *
 * Enforces rule: A Shipping Order cannot have both air_shipment and ocean_shipment.
 * The transport_mode_id is the "source of truth" for which type is allowed.
 */
class ShippingOrderShipmentService
{
    /**
     * Transport mode codes that support ocean shipments.
     */
    protected const OCEAN_MODES = ['OCEAN', 'SEA', 'FCL', 'LCL'];

    /**
     * Transport mode codes that support air shipments.
     */
    protected const AIR_MODES = ['AIR'];

    /**
     * Create or update ocean shipment details.
     *
     * @throws ValidationException if transport mode doesn't match or air shipment exists
     */
    public function upsertOceanDetails(ShippingOrder $order, array $data): OceanShipment
    {
        $order->loadMissing(['transportMode', 'airShipment']);

        $modeCode = strtoupper($order->transportMode?->code ?? '');

        // Validate transport mode is ocean
        if (!in_array($modeCode, self::OCEAN_MODES)) {
            throw ValidationException::withMessages([
                'transport_mode' => "La orden de envío no es de tipo marítimo. Modo actual: {$modeCode}",
            ]);
        }

        // Check exclusivity - no air shipment allowed
        if ($order->airShipment()->exists()) {
            throw ValidationException::withMessages([
                'shipment' => 'No puede tener detalles aéreos y marítimos a la vez. Elimine primero los detalles aéreos.',
            ]);
        }

        // Create or update ocean shipment
        return $order->oceanShipment()->updateOrCreate([], $data);
    }

    /**
     * Create or update air shipment details.
     *
     * @throws ValidationException if transport mode doesn't match or ocean shipment exists
     */
    public function upsertAirDetails(ShippingOrder $order, array $data): AirShipment
    {
        $order->loadMissing(['transportMode', 'oceanShipment']);

        $modeCode = strtoupper($order->transportMode?->code ?? '');

        // Validate transport mode is air
        if (!in_array($modeCode, self::AIR_MODES)) {
            throw ValidationException::withMessages([
                'transport_mode' => "La orden de envío no es de tipo aéreo. Modo actual: {$modeCode}",
            ]);
        }

        // Check exclusivity - no ocean shipment allowed
        if ($order->oceanShipment()->exists()) {
            throw ValidationException::withMessages([
                'shipment' => 'No puede tener detalles aéreos y marítimos a la vez. Elimine primero los detalles marítimos.',
            ]);
        }

        // Create or update air shipment
        return $order->airShipment()->updateOrCreate([], $data);
    }

    /**
     * Delete ocean shipment details.
     */
    public function deleteOceanDetails(ShippingOrder $order): bool
    {
        return $order->oceanShipment()->delete() > 0;
    }

    /**
     * Delete air shipment details.
     */
    public function deleteAirDetails(ShippingOrder $order): bool
    {
        return $order->airShipment()->delete() > 0;
    }

    /**
     * Check if a shipping order supports ocean shipment based on transport mode.
     */
    public function supportsOcean(ShippingOrder $order): bool
    {
        $order->loadMissing('transportMode');
        $modeCode = strtoupper($order->transportMode?->code ?? '');

        return in_array($modeCode, self::OCEAN_MODES);
    }

    /**
     * Check if a shipping order supports air shipment based on transport mode.
     */
    public function supportsAir(ShippingOrder $order): bool
    {
        $order->loadMissing('transportMode');
        $modeCode = strtoupper($order->transportMode?->code ?? '');

        return in_array($modeCode, self::AIR_MODES);
    }
}
