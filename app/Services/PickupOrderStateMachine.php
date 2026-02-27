<?php

namespace App\Services;

use App\Enums\PickupOrderStatus;
use App\Exceptions\InvalidPDStateTransitionException;
use App\Models\Driver;
use App\Models\PickupOrder;
use App\Models\Pod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * State machine for PickupOrder status transitions.
 *
 * Flow: pending → assigned → in_progress → completed
 *                                       ↘ cancelled
 */
class PickupOrderStateMachine
{
    /**
     * Assign a driver to the pickup order.
     * Transition: pending → assigned
     *
     * @throws InvalidPDStateTransitionException
     */
    public function assign(PickupOrder $order, Driver $driver): void
    {
        if ($order->status !== PickupOrderStatus::Pending) {
            throw new InvalidPDStateTransitionException(
                'PickupOrder',
                $order->status->value,
                'assign'
            );
        }

        $order->driver_id = $driver->id;
        $order->status = PickupOrderStatus::Assigned;
        $order->save();
    }

    /**
     * Start the pickup (driver begins route).
     * Transition: assigned → in_progress
     *
     * @throws InvalidPDStateTransitionException
     */
    public function start(PickupOrder $order): void
    {
        if ($order->status !== PickupOrderStatus::Assigned) {
            throw new InvalidPDStateTransitionException(
                'PickupOrder',
                $order->status->value,
                'start'
            );
        }

        $order->status = PickupOrderStatus::InProgress;
        $order->save();
    }

    /**
     * Complete the pickup with POD registration.
     * Transition: in_progress → completed
     * 
     * This method handles the entire POD + completion flow atomically.
     *
     * @param PickupOrder $order
     * @param array $podData ['happened_at', 'latitude', 'longitude', 'notes', 'created_by']
     * @param UploadedFile|null $image
     * @return Pod
     * @throws InvalidPDStateTransitionException
     * @throws \Illuminate\Database\QueryException (for duplicate POD)
     */
    public function completeWithPod(PickupOrder $order, array $podData, ?UploadedFile $image = null): Pod
    {
        // Validate state transition
        if ($order->status !== PickupOrderStatus::InProgress) {
            throw new InvalidPDStateTransitionException(
                'PickupOrder',
                $order->status->value,
                'complete'
            );
        }

        // Check if POD already exists (belt and suspenders - DB has unique constraint)
        if ($order->pod()->exists()) {
            throw new InvalidPDStateTransitionException(
                'PickupOrder',
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
                    "pods/pickup/{$order->id}",
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
            $order->status = PickupOrderStatus::Completed;
            $order->save();

            return $pod;
        });
    }

    /**
     * Complete the pickup without POD (legacy method).
     * Transition: in_progress → completed
     *
     * @throws InvalidPDStateTransitionException
     */
    public function complete(PickupOrder $order): void
    {
        if ($order->status !== PickupOrderStatus::InProgress) {
            throw new InvalidPDStateTransitionException(
                'PickupOrder',
                $order->status->value,
                'complete'
            );
        }

        $order->status = PickupOrderStatus::Completed;
        $order->save();
    }

    /**
     * Cancel the pickup order.
     * Transition: any (except completed) → cancelled
     *
     * @throws InvalidPDStateTransitionException
     */
    public function cancel(PickupOrder $order): void
    {
        if ($order->status === PickupOrderStatus::Completed) {
            throw new InvalidPDStateTransitionException(
                'PickupOrder',
                $order->status->value,
                'cancel',
                'No se puede cancelar una orden ya completada.'
            );
        }

        if ($order->status === PickupOrderStatus::Cancelled) {
            throw new InvalidPDStateTransitionException(
                'PickupOrder',
                $order->status->value,
                'cancel',
                'La orden ya está cancelada.'
            );
        }

        $order->status = PickupOrderStatus::Cancelled;
        $order->save();
    }

    /**
     * Check if POD can be registered for this order.
     */
    public function canRegisterPod(PickupOrder $order): bool
    {
        return $order->status === PickupOrderStatus::InProgress
            && !$order->pod()->exists();
    }

    /**
     * Get allowed transitions from current status.
     */
    public function getAllowedTransitions(PickupOrder $order): array
    {
        return match ($order->status) {
            PickupOrderStatus::Pending => ['assign', 'cancel'],
            PickupOrderStatus::Assigned => ['start', 'cancel'],
            PickupOrderStatus::InProgress => ['complete', 'cancel'],
            PickupOrderStatus::Completed => [],
            PickupOrderStatus::Cancelled => [],
        };
    }
}
