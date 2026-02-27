<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contacts.view_any');
    }

    public function view(User $user, ?Contact $contact = null): bool
    {
        return $user->can('contacts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('contacts.create');
    }

    public function update(User $user, ?Contact $contact = null): bool
    {
        return $user->can('contacts.update');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('contacts.delete');
    }

    public function restore(User $user, Contact $contact): bool
    {
        return $user->can('contacts.restore');
    }

    public function forceDelete(User $user, Contact $contact): bool
    {
        return $user->can('contacts.force_delete');
    }
}
