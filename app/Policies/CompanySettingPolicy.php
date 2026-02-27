<?php

namespace App\Policies;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CompanySettingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('company_settings.view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?CompanySetting $companySetting = null): bool
    {
        return $user->can('company_settings.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('company_settings.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?CompanySetting $companySetting = null): bool
    {
        return $user->can('company_settings.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CompanySetting $companySetting): bool
    {
        return $user->can('company_settings.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CompanySetting $companySetting): bool
    {
        return $user->can('company_settings.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CompanySetting $companySetting): bool
    {
        return $user->can('company_settings.force_delete');
    }
}
