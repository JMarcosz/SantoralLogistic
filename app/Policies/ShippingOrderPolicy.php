<?php

namespace App\Policies;

use App\Enums\ShippingOrderStatus;
use App\Models\ShippingOrder;
use App\Models\User;

class ShippingOrderPolicy
{
    /**
     * Determine whether the user can view any shipping orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('shipping_orders.view_any');
    }

    /**
     * Determine whether the user can view the shipping order.
     */
    public function view(User $user, ShippingOrder $shippingOrder): bool
    {
        return $user->can('shipping_orders.view');
    }

    /**
     * Determine whether the user can create shipping orders.
     */
    public function create(User $user): bool
    {
        return $user->can('shipping_orders.create');
    }

    /**
     * Determine whether the user can update the shipping order.
     */
    public function update(User $user, ShippingOrder $shippingOrder): bool
    {
        if (!$user->can('shipping_orders.update')) {
            return false;
        }

        // Cannot update terminal orders
        return !$shippingOrder->status->isTerminal();
    }

    /**
     * Determine whether the user can delete the shipping order.
     */
    public function delete(User $user, ShippingOrder $shippingOrder): bool
    {
        if (!$user->can('shipping_orders.delete')) {
            return false;
        }

        // Can only delete draft orders
        return $shippingOrder->status === ShippingOrderStatus::Draft;
    }

    /**
     * Determine whether the user can restore the shipping order.
     */
    public function restore(User $user, ShippingOrder $shippingOrder): bool
    {
        return $user->can('shipping_orders.restore');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ShippingOrder $shippingOrder): bool
    {
        return $user->can('shipping_orders.force_delete');
    }

    /**
     * Determine whether the user can change the order status.
     */
    public function changeStatus(User $user, ShippingOrder $shippingOrder): bool
    {
        if (!$user->can('shipping_orders.update')) {
            return false;
        }

        return !$shippingOrder->status->isTerminal();
    }

    /**
     * Determine whether the user can manage public tracking links.
     */
    public function managePublicTracking(User $user, ShippingOrder $shippingOrder): bool
    {
        return $user->can('shipping_orders.update');
    }

    /**
     * Determine whether the user can reserve inventory for this shipping order.
     * Requires inventory.transfer permission and order must be in reservable state.
     */
    public function reserveInventory(User $user, ShippingOrder $shippingOrder): bool
    {
        if (!$user->can('inventory.transfer')) {
            return false;
        }

        return $shippingOrder->canReserveInventory();
    }

    /**
     * Determine whether the user can manage charges for this shipping order.
     */
    public function manageCharges(User $user, ShippingOrder $shippingOrder): bool
    {
        // Reusing update permission, or check for specific 'billing.manage' permission if added later
        if (!$user->can('shipping_orders.update')) {
            return false;
        }

        // Allow managing charges unless the order is closed or cancelled
        // You might want to lock charges after 'Invoiced', but for now:
        return !$shippingOrder->status->isTerminal();
    }
}
