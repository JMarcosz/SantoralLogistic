<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('currencies.view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ?Currency $currency = null): bool
    {
        return $user->can('currencies.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('currencies.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ?Currency $currency = null): bool
    {
        return $user->can('currencies.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Currency $currency): bool
    {
        return $user->can('currencies.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Currency $currency): bool
    {
        return $user->can('currencies.restore');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Currency $currency): bool
    {
        return $user->can('currencies.force_delete');
    }
}
