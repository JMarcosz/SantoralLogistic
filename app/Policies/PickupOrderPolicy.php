<?php

namespace App\Policies;

use App\Models\PickupOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PickupOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any pickup orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pickup_orders.view_any');
    }

    /**
     * Determine whether the user can view the pickup order.
     */
    public function view(User $user, PickupOrder $pickupOrder): bool
    {
        return $user->can('pickup_orders.view');
    }

    /**
     * Determine whether the user can create pickup orders.
     */
    public function create(User $user): bool
    {
        return $user->can('pickup_orders.create');
    }

    /**
     * Determine whether the user can update the pickup order.
     */
    public function update(User $user, PickupOrder $pickupOrder): bool
    {
        return $user->can('pickup_orders.update');
    }

    /**
     * Determine whether the user can delete the pickup order.
     */
    public function delete(User $user, PickupOrder $pickupOrder): bool
    {
        return $user->can('pickup_orders.delete');
    }

    /**
     * Determine whether the user can assign a driver.
     */
    public function assignDriver(User $user, PickupOrder $pickupOrder): bool
    {
        return $user->can('pickup_orders.assign_driver');
    }

    /**
     * Determine whether the user can change the status (start/complete/cancel).
     */
    public function changeStatus(User $user, PickupOrder $pickupOrder): bool
    {
        return $user->can('pickup_orders.change_status');
    }
}
