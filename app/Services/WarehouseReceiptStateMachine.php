<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\WarehouseReceiptStatus;
use App\Exceptions\InvalidWarehouseReceiptTransitionException;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\WarehouseReceipt;
use Illuminate\Support\Facades\DB;

/**
 * State machine for WarehouseReceipt status transitions.
 *
 * Flow: draft → received → closed
 *                       ↘ cancelled
 */
class WarehouseReceiptStateMachine
{
    /**
     * Mark receipt as received and create inventory items.
     *
     * @throws InvalidWarehouseReceiptTransitionException
     */
    public function markReceived(WarehouseReceipt $receipt, ?int $userId = null): void
    {
        if ($receipt->status !== WarehouseReceiptStatus::Draft) {
            throw new InvalidWarehouseReceiptTransitionException(
                $receipt->status->value,
                'mark-received',
                'Solo se pueden recibir recepciones en estado borrador.'
            );
        }

        // Validate receipt has lines
        if ($receipt->lines()->count() === 0) {
            throw new InvalidWarehouseReceiptTransitionException(
                $receipt->status->value,
                'mark-received',
                'La recepción debe tener al menos una línea.'
            );
        }

        DB::transaction(function () use ($receipt, $userId) {
            $now = now();

            $receivingLocation = Location::where('warehouse_id', $receipt->warehouse_id)
                ->where('code', 'RECEIVING')
                ->first();

            // Fallback: try locations with RECV- prefix (e.g., RECV-01)
            if (!$receivingLocation) {
                $receivingLocation = Location::where('warehouse_id', $receipt->warehouse_id)
                    ->where('code', 'like', 'RECV%')
                    ->first();
            }

            if (!$receivingLocation) {
                throw new InvalidWarehouseReceiptTransitionException(
                    $receipt->status->value,
                    'mark-received',
                    'El almacén no tiene una ubicación de recepción (RECEIVING). Contacte al administrador para configurarla.'
                );
            }

            // Create inventory items from receipt lines
            foreach ($receipt->lines as $line) {
                // Create inventory item
                $inventoryItem = InventoryItem::create([
                    'warehouse_id' => $receipt->warehouse_id,
                    'customer_id' => $receipt->customer_id,
                    'warehouse_receipt_id' => $receipt->id,
                    'warehouse_receipt_line_id' => $line->id,
                    'location_id' => $receivingLocation->id, // Put in Receiving
                    'item_code' => $line->item_code,
                    'description' => $line->description,
                    'qty' => $line->received_qty,
                    'uom' => $line->uom,
                    'lot_number' => $line->lot_number,
                    'serial_number' => $line->serial_number,
                    'expiration_date' => $line->expiration_date,
                    'received_at' => $now,
                ]);

                // Record receive movement
                InventoryMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'movement_type' => MovementType::Receive,
                    'from_location_id' => null,
                    'to_location_id' => $receivingLocation->id,
                    'qty' => $line->received_qty,
                    'reference' => $receipt->receipt_number ?? "WR-{$receipt->id}",
                    'user_id' => $userId,
                    'notes' => "Recepción desde WR #{$receipt->id}",
                ]);
            }

            // Update receipt status
            $receipt->status = WarehouseReceiptStatus::Received;
            $receipt->received_at = $now;
            $receipt->save();
        });
    }

    /**
     * Close a received receipt.
     *
     * @throws InvalidWarehouseReceiptTransitionException
     */
    public function close(WarehouseReceipt $receipt): void
    {
        if ($receipt->status !== WarehouseReceiptStatus::Received) {
            throw new InvalidWarehouseReceiptTransitionException(
                $receipt->status->value,
                'close',
                'Solo se pueden cerrar recepciones recibidas.'
            );
        }

        $receipt->status = WarehouseReceiptStatus::Closed;
        $receipt->save();
    }

    /**
     * Cancel a receipt.
     *
     * @throws InvalidWarehouseReceiptTransitionException
     */
    public function cancel(WarehouseReceipt $receipt): void
    {
        if ($receipt->status === WarehouseReceiptStatus::Closed) {
            throw new InvalidWarehouseReceiptTransitionException(
                $receipt->status->value,
                'cancel',
                'No se puede cancelar una recepción cerrada.'
            );
        }

        if ($receipt->status === WarehouseReceiptStatus::Cancelled) {
            throw new InvalidWarehouseReceiptTransitionException(
                $receipt->status->value,
                'cancel',
                'La recepción ya está cancelada.'
            );
        }

        $receipt->status = WarehouseReceiptStatus::Cancelled;
        $receipt->save();
    }

    /**
     * Get allowed transitions from current status.
     */
    public function getAllowedTransitions(WarehouseReceipt $receipt): array
    {
        return match ($receipt->status) {
            WarehouseReceiptStatus::Draft => ['mark-received', 'cancel'],
            WarehouseReceiptStatus::Received => ['close', 'cancel'],
            WarehouseReceiptStatus::Closed => [],
            WarehouseReceiptStatus::Cancelled => [],
        };
    }

    /**
     * Check if receipt can be edited.
     */
    public function canEdit(WarehouseReceipt $receipt): bool
    {
        return $receipt->status === WarehouseReceiptStatus::Draft;
    }
}
