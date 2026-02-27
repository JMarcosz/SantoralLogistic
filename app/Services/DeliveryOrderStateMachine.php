<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Exceptions\InvalidPDStateTransitionException;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Pod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * State machine for DeliveryOrder status transitions.
 *
 * Flow: pending → assigned → in_progress → completed
 *                                       ↘ cancelled
 */
class DeliveryOrderStateMachine
{
    /**
     * Assign a driver to the delivery order.
     * Transition: pending → assigned
     *
     * @throws InvalidPDStateTransitionException
     */
    public function assign(DeliveryOrder $order, Driver $driver): void
    {
        if ($order->status !== DeliveryOrderStatus::Pending) {
            throw new InvalidPDStateTransitionException(
                'DeliveryOrder',
                $order->status->value,
                'assign'
            );
        }

        $order->driver_id = $driver->id;
        $order->status = DeliveryOrderStatus::Assigned;
        $order->save();
    }

    /**
     * Start the delivery (driver begins route).
     * Transition: assigned → in_progress
     *
     * @throws InvalidPDStateTransitionException
     */
    public function start(DeliveryOrder $order): void
    {
        if ($order->status !== DeliveryOrderStatus::Assigned) {
            throw new InvalidPDStateTransitionException(
                'DeliveryOrder',
                $order->status->value,
                'start'
            );
        }

        $order->status = DeliveryOrderStatus::InProgress;
        $order->save();
    }

    /**
     * Complete the delivery with POD registration.
     * Transition: in_progress → completed
     * 
     * This method handles the entire POD + completion flow atomically.
     *
     * @param DeliveryOrder $order
     * @param array $podData ['happened_at', 'latitude', 'longitude', 'notes', 'created_by']
     * @param UploadedFile|null $image
     * @return Pod
     * @throws InvalidPDStateTransitionException
     * @throws \Illuminate\Database\QueryException (for duplicate POD)
     */
    public function completeWithPod(DeliveryOrder $order, array $podData, ?UploadedFile $image = null): Pod
    {
        // Validate state transition
        if ($order->status !== DeliveryOrderStatus::InProgress) {
            throw new InvalidPDStateTransitionException(
                'DeliveryOrder',
                $order->status->value,
                'complete'
            );
        }

        // Check if POD already exists (belt and suspenders - DB has unique constraint)
        if ($order->pod()->exists()) {
            throw new InvalidPDStateTransitionException(
                'DeliveryOrder',
                $order->status->value,
                'complete',
                'Esta orden ya tiene un POD registrado.'
            );
        }

        return DB::transaction(function () use ($order, $podData, $image) {
            // Handle image upload inside transaction
            $imagePath = null;
            if ($image) {
                $imagePath = $image->store(
                    "pods/delivery/{$order->id}",
                    config('filesystems.default', 'local')
                );
            }

            // Create POD record
            $pod = $order->pod()->create([
                'happened_at' => $podData['happened_at'],
                'latitude' => $podData['latitude'] ?? null,
                'longitude' => $podData['longitude'] ?? null,
                'image_path' => $imagePath,
                'notes' => $podData['notes'] ?? null,
                'created_by' => $podData['created_by'] ?? null,
            ]);

            // Transition to completed
            $order->status = DeliveryOrderStatus::Completed;
            $order->save();

            return $pod;
        });
    }

    /**
     * Complete the delivery without POD (legacy method).
     * Transition: in_progress → completed
     *
     * @throws InvalidPDStateTransitionException
     */
    public function complete(DeliveryOrder $order): void
    {
        if ($order->status !== DeliveryOrderStatus::InProgress) {
            throw new InvalidPDStateTransitionException(
                'DeliveryOrder',
                $order->status->value,
                'complete'
            );
        }

        $order->status = DeliveryOrderStatus::Completed;
        $order->save();
    }

    /**
     * Cancel the delivery order.
     * Transition: any (except completed) → cancelled
     *
     * @throws InvalidPDStateTransitionException
     */
    public function cancel(DeliveryOrder $order): void
    {
        if ($order->status === DeliveryOrderStatus::Completed) {
            throw new InvalidPDStateTransitionException(
                'DeliveryOrder',
                $order->status->value,
                'cancel',
                'No se puede cancelar una orden ya completada.'
            );
        }

        if ($order->status === DeliveryOrderStatus::Cancelled) {
            throw new InvalidPDStateTransitionException(
                'DeliveryOrder',
                $order->status->value,
                'cancel',
                'La orden ya está cancelada.'
            );
        }

        $order->status = DeliveryOrderStatus::Cancelled;
        $order->save();
    }

    /**
     * Check if POD can be registered for this order.
     */
    public function canRegisterPod(DeliveryOrder $order): bool
    {
        return $order->status === DeliveryOrderStatus::InProgress
            && !$order->pod()->exists();
    }

    /**
     * Get allowed transitions from current status.
     */
    public function getAllowedTransitions(DeliveryOrder $order): array
    {
        return match ($order->status) {
            DeliveryOrderStatus::Pending => ['assign', 'cancel'],
            DeliveryOrderStatus::Assigned => ['start', 'cancel'],
            DeliveryOrderStatus::InProgress => ['complete', 'cancel'],
            DeliveryOrderStatus::Completed => [],
            DeliveryOrderStatus::Cancelled => [],
        };
    }
}
