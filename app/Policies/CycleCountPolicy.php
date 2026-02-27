<?php

namespace App\Policies;

use App\Models\CycleCount;
use App\Models\User;

class CycleCountPolicy
{
    /**
     * Determine whether the user can view any cycle counts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('cycle_counts.view_any');
    }

    /**
     * Determine whether the user can view the cycle count.
     */
    public function view(User $user, CycleCount $cycleCount): bool
    {
        return $user->hasPermissionTo('cycle_counts.view');
    }

    /**
     * Determine whether the user can create cycle counts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('cycle_counts.create');
    }

    /**
     * Determine whether the user can update the cycle count.
     */
    public function update(User $user, CycleCount $cycleCount): bool
    {
        if (!$user->hasPermissionTo('cycle_counts.update')) {
            return false;
        }

        return !$cycleCount->status->isTerminal();
    }

    /**
     * Determine whether the user can complete the cycle count.
     */
    public function complete(User $user, CycleCount $cycleCount): bool
    {
        if (!$user->hasPermissionTo('cycle_counts.complete')) {
            return false;
        }

        return $cycleCount->canComplete();
    }

    /**
     * Determine whether the user can cancel the cycle count.
     */
    public function cancel(User $user, CycleCount $cycleCount): bool
    {
        if (!$user->hasPermissionTo('cycle_counts.cancel')) {
            return false;
        }

        return $cycleCount->canCancel();
    }
}
