<?php

namespace App\Policies;

use App\Models\TransportMode;
use App\Models\User;

class TransportModePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transport_modes.view_any');
    }

    public function view(User $user, ?TransportMode $transportMode = null): bool
    {
        return $user->can('transport_modes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('transport_modes.create');
    }

    public function update(User $user, ?TransportMode $transportMode = null): bool
    {
        return $user->can('transport_modes.update');
    }

    public function delete(User $user, TransportMode $transportMode): bool
    {
        return $user->can('transport_modes.delete');
    }

    public function restore(User $user, TransportMode $transportMode): bool
    {
        return $user->can('transport_modes.restore');
    }

    public function forceDelete(User $user, TransportMode $transportMode): bool
    {
        return $user->can('transport_modes.force_delete');
    }
}
