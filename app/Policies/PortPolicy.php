<?php

namespace App\Policies;

use App\Models\Port;
use App\Models\User;

class PortPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ports.view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?Port $port = null): bool
    {
        return $user->can('ports.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('ports.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?Port $port = null): bool
    {
        return $user->can('ports.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Port $port): bool
    {
        return $user->can('ports.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Port $port): bool
    {
        return $user->can('ports.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Port $port): bool
    {
        return $user->can('ports.force_delete');
    }
}
