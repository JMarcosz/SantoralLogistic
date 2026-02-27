<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any payments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payments.view');
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, Payment $payment): bool
    {
        if (!$user->can('payments.update')) {
            return false;
        }

        // Can only edit draft/pending payments
        return $payment->canEdit();
    }

    /**
     * Determine whether the user can delete the payment.
     */
    public function delete(User $user, Payment $payment): bool
    {
        if (!$user->can('payments.delete')) {
            return false;
        }

        // Can only delete draft/pending payments
        return $payment->canDelete();
    }

    /**
     * Determine whether the user can post the payment.
     */
    public function post(User $user, Payment $payment): bool
    {
        if (!$user->can('payments.post')) {
            return false;
        }

        return $payment->canPost();
    }

    /**
     * Determine whether the user can void the payment.
     */
    public function void(User $user, Payment $payment): bool
    {
        if (!$user->can('payments.void')) {
            return false;
        }

        return $payment->canVoid();
    }
}
