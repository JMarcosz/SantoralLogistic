<?php

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    /**
     * Determine whether the user can view any quotes.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('quotes.view_any');
    }

    /**
     * Determine whether the user can view the quote.
     */
    public function view(User $user, Quote $quote): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        // Standard users can only view their own quotes
        return $this->isOwnedBy($user, $quote) && $user->can('quotes.view');
    }

    /**
     * Determine whether the user can create quotes.
     */
    public function create(User $user): bool
    {
        return $user->can('quotes.create');
    }

    /**
     * Determine whether the user can update the quote.
     */
    public function update(User $user, Quote $quote): bool
    {
        // Admin override
        if ($this->isAdmin($user)) {
            return true;
        }

        if (!$user->can('quotes.update')) {
            return false;
        }

        // Can only update own quotes in draft status
        return $this->isOwnedBy($user, $quote) && $quote->status === QuoteStatus::Draft;
    }

    /**
     * Determine whether the user can delete the quote.
     */
    public function delete(User $user, Quote $quote): bool
    {
        // Admin override
        if ($this->isAdmin($user)) {
            return true;
        }

        if (!$user->can('quotes.delete')) {
            return false;
        }

        // Can only delete own draft quotes
        return $this->isOwnedBy($user, $quote) && $quote->status === QuoteStatus::Draft;
    }

    /**
     * Determine whether the user can send the quote.
     */
    public function send(User $user, Quote $quote): bool
    {
        // Admin override
        if ($this->isAdmin($user)) {
            return $quote->status === QuoteStatus::Draft;
        }

        if (!$user->can('quotes.send')) {
            return false;
        }

        // Standard users: must own the quote
        return $this->isOwnedBy($user, $quote) && $quote->status === QuoteStatus::Draft;
    }

    /**
     * Determine whether the user can approve the quote.
     */
    public function approve(User $user, Quote $quote): bool
    {
        // Admin override
        if ($this->isAdmin($user)) {
            return $quote->status === QuoteStatus::Sent;
        }

        if (!$user->can('quotes.approve')) {
            return false;
        }

        // Standard users: must own the quote (if business allows)
        return $this->isOwnedBy($user, $quote) && $quote->status === QuoteStatus::Sent;
    }

    /**
     * Determine whether the user can reject the quote.
     */
    public function reject(User $user, Quote $quote): bool
    {
        // Admin override
        if ($this->isAdmin($user)) {
            return $quote->status === QuoteStatus::Sent;
        }

        if (!$user->can('quotes.reject')) {
            return false;
        }

        // Standard users: must own the quote
        return $this->isOwnedBy($user, $quote) && $quote->status === QuoteStatus::Sent;
    }

    /**
     * Determine whether the user can convert the quote to a shipping order.
     */
    public function convertToShippingOrder(User $user, Quote $quote): bool
    {
        // Check if already converted (Business Logic applies to everyone)
        // Accessing DB directly might be heavy but necessary for integrity
        // OR rely on controller to handle it. Policy should ideally check usage.
        if (\App\Models\ShippingOrder::where('quote_id', $quote->id)->exists()) {
            return false;
        }

        // Admin override
        if ($this->isAdmin($user)) {
            return $quote->status === QuoteStatus::Approved;
        }

        if (!$user->can('quotes.convert_to_shipping_order')) {
            return false;
        }

        if ($quote->status !== QuoteStatus::Approved) {
            return false;
        }

        // Standard users: must own the quote
        return $this->isOwnedBy($user, $quote);
    }

    /**
     * Determine whether the user can restore the quote.
     */
    public function restore(User $user, Quote $quote): bool
    {
        return $user->can('quotes.restore');
    }

    /**
     * Determine whether the user can permanently delete the quote.
     */
    public function forceDelete(User $user, Quote $quote): bool
    {
        return $user->can('quotes.force_delete');
    }

    /**
     * Determine whether the user can view financial details (cost/profit).
     */
    public function viewFinancials(User $user, Quote $quote): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->can('quotes.view_financials');
    }

    /**
     * Check if user is an admin/super-admin/owner.
     */
    protected function isAdmin(User $user): bool
    {
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'super_admin', 'super-admin', 'owner']);
        }
        return $user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('super-admin') || $user->hasRole('owner');
    }

    /**
     * Check if user owns the quote (created_by or sales_rep_id).
     */
    protected function isOwnedBy(User $user, Quote $quote): bool
    {
        return $user->id === $quote->created_by || $user->id === $quote->sales_rep_id;
    }
}
