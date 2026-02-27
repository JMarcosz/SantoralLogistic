<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryMovementService
{
    /**
     * Putaway: Assign a location to an unlocated inventory item.
     *
     * This operation is used when items are received in the warehouse but have not
     * yet been assigned a storage location. It creates a movement record for audit
     * purposes and updates the item's location.
     *
     * @param InventoryItem $item The inventory item to locate (must not have a location)
     * @param Location $location The target location to assign
     * @return InventoryMovement The created movement record
     * @throws \InvalidArgumentException When the item already has a location assigned
     */
    public function putaway(InventoryItem $item, Location $location): InventoryMovement
    {
        if ($item->location_id !== null) {
            throw new \InvalidArgumentException('Este ítem ya tiene una ubicación asignada.');
        }

        if ($location->warehouse_id !== $item->warehouse_id) {
            throw new \InvalidArgumentException('La ubicación debe pertenecer al mismo almacén que el ítem.');
        }

        return DB::transaction(function () use ($item, $location) {
            // Update item location
            $item->update(['location_id' => $location->id]);

            // Log the movement
            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'movement_type' => MovementType::Putaway,
                'from_location_id' => null,
                'to_location_id' => $location->id,
                'qty' => $item->qty,
                'reference' => 'Putaway',
                'user_id' => Auth::id(),
                'notes' => null,
            ]);

            Log::info('Inventory putaway completed', [
                'item_id' => $item->id,
                'item_code' => $item->item_code,
                'location_id' => $location->id,
                'location_code' => $location->code,
                'qty' => $item->qty,
                'user_id' => Auth::id(),
            ]);

            return $movement;
        });
    }

    /**
     * Relocate: Move quantity from one location to another.
     *
     * This operation handles both full and partial movements:
     * - Partial: Reduces the source item quantity and either creates a new item
     *   at the destination or adds to an existing item with matching attributes.
     * - Full: Simply updates the source item's location.
     *
     * For partial moves, the system checks for consolidation opportunities with
     * existing items at the destination that have matching SKU, lot_number, and
     * serial_number values.
     *
     * @param InventoryItem $item The source inventory item
     * @param Location $toLocation The destination location
     * @param float $qty The quantity to move (must be > 0 and <= item's current qty)
     * @param string|null $notes Optional notes for the movement record
     * @return InventoryMovement The created movement record
     * @throws \InvalidArgumentException When validation fails (no location, same location, invalid qty)
     */
    public function relocate(
        InventoryItem $item,
        Location $toLocation,
        float $qty,
        ?string $notes = null
    ): InventoryMovement {
        if ($item->location_id === null) {
            throw new \InvalidArgumentException('Este ítem no tiene ubicación asignada. Use Putaway.');
        }

        if ($item->location_id === $toLocation->id) {
            throw new \InvalidArgumentException('La ubicación de destino es la misma que la origen.');
        }

        if ($toLocation->warehouse_id !== $item->warehouse_id) {
            throw new \InvalidArgumentException('La ubicación destino debe pertenecer al mismo almacén.');
        }

        if ($qty <= 0 || $qty > $item->qty) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0 y no exceder la cantidad disponible.');
        }

        // Check available quantity considering reservations
        $availableQty = $item->availableQuantity();
        if ($qty > $availableQty) {
            throw new \InvalidArgumentException(
                "La cantidad a mover ({$qty}) excede la cantidad disponible ({$availableQty}) considerando reservas."
            );
        }

        return DB::transaction(function () use ($item, $toLocation, $qty, $notes) {
            $fromLocationId = $item->location_id;
            $isPartial = $qty < $item->qty;

            if ($isPartial) {
                // Reduce original item
                $item->update(['qty' => $item->qty - $qty]);

                // Check if destination location already has same SKU from same customer
                // NO - We do not merge items anymore (Stock Lots concept)
                // We always create a new split item to preserve receipt traceability

                // Create new inventory item at destination
                InventoryItem::create([
                    'warehouse_id' => $item->warehouse_id,
                    'customer_id' => $item->customer_id,
                    'location_id' => $toLocation->id,
                    'warehouse_receipt_id' => $item->warehouse_receipt_id,
                    'warehouse_receipt_line_id' => $item->warehouse_receipt_line_id,
                    'item_code' => $item->item_code,
                    'description' => $item->description,
                    'qty' => $qty,
                    'uom' => $item->uom,
                    'lot_number' => $item->lot_number,
                    'serial_number' => $item->serial_number,
                    'expiration_date' => $item->expiration_date,
                    'received_at' => $item->received_at,
                ]);
            } else {
                // Move entire quantity - just update location
                $item->update(['location_id' => $toLocation->id]);
            }

            // Log the movement
            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'movement_type' => MovementType::Transfer,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocation->id,
                'qty' => $qty,
                'reference' => 'Relocate',
                'user_id' => Auth::id(),
                'notes' => $notes,
            ]);

            Log::info('Inventory relocated', [
                'item_id' => $item->id,
                'item_code' => $item->item_code,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocation->id,
                'qty' => $qty,
                'is_partial' => $isPartial,
                'user_id' => Auth::id(),
            ]);

            return $movement;
        });
    }

    /**
     * Adjust: Change the quantity with a reason (for count adjustments, damage, etc.)
     *
     * This operation records quantity adjustments for various reasons such as:
     * - Physical count corrections
     * - Damage write-offs
     * - Expiration losses
     * - Error corrections
     *
     * The delta (difference) is stored in the movement record, which can be positive
     * or negative depending on whether inventory increased or decreased.
     *
     * @param InventoryItem $item The inventory item to adjust
     * @param float $newQty The new quantity (must be >= 0)
     * @param string $reason The reason code for the adjustment
     * @param string|null $notes Optional additional notes
     * @return InventoryMovement The created movement record
     * @throws \InvalidArgumentException When the new quantity is negative
     */
    public function adjust(
        InventoryItem $item,
        float $newQty,
        string $reason,
        ?string $notes = null
    ): InventoryMovement {
        if ($newQty < 0) {
            throw new \InvalidArgumentException('La cantidad no puede ser negativa.');
        }

        // Check if adjustment would cause over-reservation
        $reservedQty = $item->reservedQuantity();
        if ($newQty < $reservedQty) {
            throw new \InvalidArgumentException(
                "La nueva cantidad ({$newQty}) es menor que la cantidad reservada ({$reservedQty}). Libere las reservas primero."
            );
        }

        return DB::transaction(function () use ($item, $newQty, $reason, $notes) {
            $oldQty = $item->qty;
            $delta = $newQty - $oldQty;

            // Update the quantity
            $item->update(['qty' => $newQty]);

            // Log the movement
            $movement = InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'movement_type' => MovementType::Adjust,
                'from_location_id' => $item->location_id,
                'to_location_id' => $item->location_id,
                'qty' => $delta, // This is the delta (can be negative)
                'reference' => $reason,
                'user_id' => Auth::id(),
                'notes' => $notes,
            ]);

            Log::info('Inventory adjusted', [
                'item_id' => $item->id,
                'item_code' => $item->item_code,
                'old_qty' => $oldQty,
                'new_qty' => $newQty,
                'delta' => $delta,
                'reason' => $reason,
                'user_id' => Auth::id(),
            ]);

            return $movement;
        });
    }

    /**
     * Get movement history for an inventory item.
     *
     * Returns the most recent movements for the specified item, ordered by
     * creation date descending (newest first). Each movement includes the
     * related user and location information.
     *
     * @param InventoryItem $item The inventory item to get history for
     * @param int $limit Maximum number of movements to return (default: 50)
     * @return Collection<InventoryMovement> Collection of movement records
     */
    public function getMovements(InventoryItem $item, int $limit = 50): Collection
    {
        return $item->movements()
            ->with(['user', 'fromLocation', 'toLocation'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
