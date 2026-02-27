<?php

namespace App\Policies;

use App\Models\PackageType;
use App\Models\User;

class PackageTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('package_types.view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?PackageType $packageType = null): bool
    {
        return $user->can('package_types.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('package_types.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?PackageType $packageType = null): bool
    {
        return $user->can('package_types.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PackageType $packageType): bool
    {
        return $user->can('package_types.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PackageType $packageType): bool
    {
        return $user->can('package_types.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PackageType $packageType): bool
    {
        return $user->can('package_types.force_delete');
    }
}
