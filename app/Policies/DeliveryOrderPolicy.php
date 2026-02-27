<?php

namespace App\Policies;

use App\Models\DeliveryOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any delivery orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('delivery_orders.view_any');
    }

    /**
     * Determine whether the user can view the delivery order.
     */
    public function view(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $user->can('delivery_orders.view');
    }

    /**
     * Determine whether the user can create delivery orders.
     */
    public function create(User $user): bool
    {
        return $user->can('delivery_orders.create');
    }

    /**
     * Determine whether the user can update the delivery order.
     */
    public function update(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $user->can('delivery_orders.update');
    }

    /**
     * Determine whether the user can delete the delivery order.
     */
    public function delete(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $user->can('delivery_orders.delete');
    }

    /**
     * Determine whether the user can assign a driver.
     */
    public function assignDriver(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $user->can('delivery_orders.assign_driver');
    }

    /**
     * Determine whether the user can change the status (start/complete/cancel).
     */
    public function changeStatus(User $user, DeliveryOrder $deliveryOrder): bool
    {
        return $user->can('delivery_orders.change_status');
    }
}
