<?php

namespace App\Providers;

use App\Models\JournalEntry;
use App\Policies\JournalEntryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        JournalEntry::class => JournalEntryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Invoice permissions
        Gate::define('invoices.view_any', function ($user) {
            return true; // For testing - allow all users
        });

        Gate::define('invoices.generate', function ($user) {
            return true; // For testing - allow all users
        });

        Gate::define('generateInvoice', function ($user, $preInvoice) {
            return true; // For testing - allow all users
        });
    }
}
