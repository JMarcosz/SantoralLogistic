<?php

namespace App\Providers;

use App\Events\PaymentPosted;
use App\Events\PaymentVoided;
use App\Listeners\CreatePaymentJournalEntry;
use App\Listeners\ReversePaymentJournalEntry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register payment accounting events
        Event::listen(PaymentPosted::class, CreatePaymentJournalEntry::class);
        Event::listen(PaymentVoided::class, ReversePaymentJournalEntry::class);

        // Register policies explicitly (Laravel 12)
        Gate::policy(\App\Models\Term::class, \App\Policies\TermPolicy::class);
        Gate::policy(\App\Models\Payment::class, \App\Policies\PaymentPolicy::class);

        // Super admin bypasses all permission checks EXCEPT for specific business rules
        Gate::before(function ($user, $ability, $arguments = []) {
            if (!$user->hasRole('super_admin')) {
                return null; // Not super admin, continue to normal policy checks
            }

            // Business rule: Prevent deleting yourself
            if ($ability === 'delete' && !empty($arguments)) {
                $model = $arguments[0] ?? null;
                if ($model instanceof \App\Models\User && $model->id === $user->id) {
                    return false; // Deny even for super_admin
                }
            }

            // Business rule: Prevent deleting super_admin role
            if ($ability === 'delete' && !empty($arguments)) {
                $model = $arguments[0] ?? null;
                if ($model instanceof \Spatie\Permission\Models\Role && $model->name === 'super_admin') {
                    return false; // Deny even for super_admin
                }
            }

            // Business rule: Let Quote state-dependent actions go through Policy
            // to ensure proper state machine validation
            $quoteStateActions = ['send', 'approve', 'reject', 'convertToShippingOrder', 'update', 'delete'];
            if (in_array($ability, $quoteStateActions) && !empty($arguments)) {
                $model = $arguments[0] ?? null;
                if ($model instanceof \App\Models\Quote) {
                    return null; // Let the policy decide based on state
                }
            }

            // Business rule: Let JournalEntry state-dependent actions go through Policy
            // to ensure proper status validation (can't post already posted, etc.)
            $journalEntryStateActions = ['post', 'reverse', 'update', 'delete'];
            if (in_array($ability, $journalEntryStateActions) && !empty($arguments)) {
                $model = $arguments[0] ?? null;
                if ($model instanceof \App\Models\JournalEntry) {
                    return null; // Let the policy decide based on status
                }
            }

            // Super admin allowed for everything else
            return true;
        });
    }
}
