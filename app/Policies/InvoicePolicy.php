<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any invoices.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view_any');
    }

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view');
    }

    /**
     * Determine whether the user can create invoices.
     */
    public function create(User $user): bool
    {
        return $user->can('invoices.create');
    }

    /**
     * Determine whether the user can cancel the invoice.
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        // Can only cancel issued invoices
        if ($invoice->status !== Invoice::STATUS_ISSUED) {
            return false;
        }

        return $user->can('invoices.cancel');
    }

    /**
     * Determine whether the user can print the invoice.
     */
    public function print(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.print');
    }

    /**
     * Determine whether the user can email the invoice.
     */
    public function email(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.email');
    }

    /**
     * Determine whether the user can batch export invoices.
     */
    public function export(User $user): bool
    {
        return $user->can('invoices.export');
    }

    /**
     * Determine whether the user can view analytics.
     */
    public function analytics(User $user): bool
    {
        return $user->can('invoices.analytics');
    }
}
