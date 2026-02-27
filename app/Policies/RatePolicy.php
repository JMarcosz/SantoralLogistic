<?php

namespace App\Policies;

use App\Models\Rate;
use App\Models\User;

class RatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('rates.view_any');
    }

    public function view(User $user, ?Rate $rate = null): bool
    {
        return $user->can('rates.view');
    }

    public function create(User $user): bool
    {
        return $user->can('rates.create');
    }

    public function update(User $user, ?Rate $rate = null): bool
    {
        return $user->can('rates.update');
    }

    public function delete(User $user, Rate $rate): bool
    {
        return $user->can('rates.delete');
    }

    public function restore(User $user, Rate $rate): bool
    {
        return $user->can('rates.restore');
    }

    public function forceDelete(User $user, Rate $rate): bool
    {
        return $user->can('rates.force_delete');
    }
}
