<?php

namespace App\Policies;

use App\Models\Term;
use App\Models\User;

class TermPolicy
{
    /**
     * Determine whether the user can view any terms.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['terms.view_any', 'settings.manage']);
    }

    /**
     * Determine whether the user can view the term.
     */
    public function view(User $user, Term $term): bool
    {
        return $user->hasAnyPermission(['terms.view', 'terms.view_any', 'settings.manage']);
    }

    /**
     * Determine whether the user can create terms.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['terms.create', 'settings.manage']);
    }

    /**
     * Determine whether the user can update the term.
     */
    public function update(User $user, Term $term): bool
    {
        return $user->hasAnyPermission(['terms.update', 'settings.manage']);
    }

    /**
     * Determine whether the user can delete the term.
     */
    public function delete(User $user, Term $term): bool
    {
        return $user->hasAnyPermission(['terms.delete', 'settings.manage']);
    }
}
