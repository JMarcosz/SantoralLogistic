<?php

namespace App\Policies;

use App\Enums\WarehouseOrderStatus;
use App\Models\User;
use App\Models\WarehouseOrder;

class WarehouseOrderPolicy
{
    /**
     * Determine whether the user can view any warehouse orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('warehouse_orders.view_any');
    }

    /**
     * Determine whether the user can view the warehouse order.
     */
    public function view(User $user, WarehouseOrder $warehouseOrder): bool
    {
        return $user->hasPermissionTo('warehouse_orders.view');
    }

    /**
     * Determine whether the user can create warehouse orders.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('warehouse_orders.create');
    }

    /**
     * Determine whether the user can update the warehouse order.
     */
    public function update(User $user, WarehouseOrder $warehouseOrder): bool
    {
        if (!$user->hasPermissionTo('warehouse_orders.update')) {
            return false;
        }

        // Cannot update terminal orders
        return !$warehouseOrder->status->isTerminal();
    }

    /**
     * Determine whether the user can dispatch the warehouse order.
     */
    public function dispatch(User $user, WarehouseOrder $warehouseOrder): bool
    {
        if (!$user->hasPermissionTo('warehouse_orders.dispatch')) {
            return false;
        }

        return $warehouseOrder->canMarkDispatched();
    }

    /**
     * Determine whether the user can cancel the warehouse order.
     */
    public function cancel(User $user, WarehouseOrder $warehouseOrder): bool
    {
        if (!$user->hasPermissionTo('warehouse_orders.cancel')) {
            return false;
        }

        return $warehouseOrder->canCancel();
    }
}
