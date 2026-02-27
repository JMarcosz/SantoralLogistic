<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    /**
     * Determine whether the user can view any inventory items.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventory.view_any');
    }

    /**
     * Determine whether the user can view the inventory item.
     */
    public function view(User $user, InventoryItem $item): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }

    /**
     * Determine whether the user can perform putaway operation.
     */
    public function putaway(User $user, InventoryItem $item): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }

    /**
     * Determine whether the user can relocate/transfer inventory.
     */
    public function relocate(User $user, InventoryItem $item): bool
    {
        return $user->hasPermissionTo('inventory.transfer');
    }

    /**
     * Determine whether the user can adjust inventory quantities.
     */
    public function adjust(User $user, InventoryItem $item): bool
    {
        return $user->hasPermissionTo('inventory.adjust');
    }

    /**
     * Determine whether the user can view movement history.
     */
    public function viewMovements(User $user, InventoryItem $item): bool
    {
        return $user->hasPermissionTo('inventory.view');
    }
}
