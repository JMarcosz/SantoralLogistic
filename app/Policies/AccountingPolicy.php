<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Accounting Policy
 * 
 * Controls access to accounting module and operations.
 * Enforces immutability of posted journal entries.
 */
class AccountingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view accounting module.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('accounting.view');
    }

    /**
     * Determine if user can manage accounts and settings.
     */
    public function manage(User $user): bool
    {
        return $user->can('accounting.manage');
    }

    /**
     * Determine if user can post journal entries.
     */
    public function post(User $user): bool
    {
        return $user->can('accounting.post');
    }

    /**
     * Determine if user can close accounting periods.
     */
    public function closePeriod(User $user): bool
    {
        return $user->can('accounting.close_period');
    }

    /**
     * Determine if user can edit a journal entry.
     * 
     * CRITICAL: Posted entries are IMMUTABLE.
     */
    public function update(User $user, $journalEntry): bool
    {
        // Cannot edit if posted
        if (isset($journalEntry->status) && $journalEntry->status === 'posted') {
            return false;
        }

        // Cannot edit if reversed
        if (isset($journalEntry->status) && $journalEntry->status === 'reversed') {
            return false;
        }

        return $user->can('accounting.manage');
    }

    /**
     * Determine if user can delete a journal entry.
     * 
     * CRITICAL: Posted entries cannot be deleted, only reversed.
     */
    public function delete(User $user, $journalEntry): bool
    {
        // Cannot delete if posted
        if (isset($journalEntry->status) && $journalEntry->status === 'posted') {
            return false;
        }

        // Cannot delete if reversed
        if (isset($journalEntry->status) && $journalEntry->status === 'reversed') {
            return false;
        }

        // Only drafts can be deleted
        return $user->can('accounting.manage')
            && (isset($journalEntry->status) && $journalEntry->status === 'draft');
    }

    /**
     * Determine if user can reverse a journal entry.
     */
    public function reverse(User $user, $journalEntry): bool
    {
        // Can only reverse posted entries
        if (!isset($journalEntry->status) || $journalEntry->status !== 'posted') {
            return false;
        }

        // Cannot reverse if already reversed
        if (isset($journalEntry->reversed_at)) {
            return false;
        }

        return $user->can('accounting.post');
    }
}
