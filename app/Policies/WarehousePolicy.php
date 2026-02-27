<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    /**
     * Determine whether the user can view any warehouses.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('warehouses.view_any');
    }

    /**
     * Determine whether the user can view the warehouse.
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermissionTo('warehouses.view');
    }

    /**
     * Determine whether the user can create warehouses.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('warehouses.create');
    }

    /**
     * Determine whether the user can update the warehouse.
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermissionTo('warehouses.update');
    }

    /**
     * Determine whether the user can delete the warehouse.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermissionTo('warehouses.delete');
    }
}
