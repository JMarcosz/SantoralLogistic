<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    /**
     * Determine whether the user can view any locations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('locations.view_any');
    }

    /**
     * Determine whether the user can view the location.
     */
    public function view(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('locations.view');
    }

    /**
     * Determine whether the user can create locations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('locations.create');
    }

    /**
     * Determine whether the user can update the location.
     */
    public function update(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('locations.update');
    }

    /**
     * Determine whether the user can delete the location.
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('locations.delete');
    }
}
