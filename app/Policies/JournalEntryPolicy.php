<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('journal_entries.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('journal_entries.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.edit') && $entry->canEdit();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.delete') && $entry->canDelete();
    }

    /**
     * Determine whether the user can post the model.
     */
    public function post(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.post') && $entry->canPost();
    }

    /**
     * Determine whether the user can reverse the model.
     */
    public function reverse(User $user, JournalEntry $entry): bool
    {
        return $user->can('journal_entries.reverse') && $entry->canReverse();
    }
}
