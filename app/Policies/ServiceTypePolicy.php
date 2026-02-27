<?php

namespace App\Policies;

use App\Models\ServiceType;
use App\Models\User;

class ServiceTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('service_types.view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?ServiceType $serviceType = null): bool
    {
        return $user->can('service_types.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('service_types.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?ServiceType $serviceType = null): bool
    {
        return $user->can('service_types.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ServiceType $serviceType): bool
    {
        return $user->can('service_types.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ServiceType $serviceType): bool
    {
        return $user->can('service_types.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ServiceType $serviceType): bool
    {
        return $user->can('service_types.force_delete');
    }
}
