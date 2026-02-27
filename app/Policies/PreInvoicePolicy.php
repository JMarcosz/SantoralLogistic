<?php

namespace App\Policies;

use App\Models\PreInvoice;
use App\Models\User;

class PreInvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pre_invoices.view_any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PreInvoice $preInvoice): bool
    {
        return $user->can('pre_invoices.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('pre_invoices.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PreInvoice $preInvoice): bool
    {
        return $user->can('pre_invoices.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PreInvoice $preInvoice): bool
    {
        return $user->can('pre_invoices.delete');
    }

    /**
     * Determine if the user can generate a fiscal invoice from this pre-invoice.
     */
    public function generateInvoice(User $user, PreInvoice $preInvoice): bool
    {
        // Check permission
        if (!$user->can('invoices.create')) {
            return false;
        }

        // Business rule: PreInvoice must be in facturable status
        if (!$preInvoice->canBeInvoiced()) {
            return false;
        }

        return true;
    }
}
