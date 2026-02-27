<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view_any');
    }

    public function view(User $user, ?Customer $customer = null): bool
    {
        return $user->can('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    public function update(User $user, ?Customer $customer = null): bool
    {
        return $user->can('customers.update');
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('customers.restore');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->can('customers.force_delete');
    }
}
