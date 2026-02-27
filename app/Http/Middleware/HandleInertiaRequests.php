<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'can' => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->toArray()
                    : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn() => Session::get('success'),
                'error' => fn() => Session::get('error'),
                'info' => fn() => Session::get('info'),
            ],
            'company' => fn() => $this->getCompanySettings(),
        ];
    }

    /**
     * Get company settings with caching.
     */
    protected function getCompanySettings(): ?array
    {
        return Cache::remember('company_settings', 3600, function () {
            $company = CompanySetting::first();

            if (!$company) {
                return null;
            }

            return [
                'id' => $company->id,
                'name' => $company->name,
                'rnc' => $company->rnc,
                'address' => $company->address,
                'phone' => $company->phone,
                'email' => $company->email,
                'website' => $company->website,
                'logo_url' => $company->logo_url,
                'is_active' => $company->is_active,
            ];
        });
    }
}
