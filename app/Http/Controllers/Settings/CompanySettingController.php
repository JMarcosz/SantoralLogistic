<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanySettingRequest;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingController extends Controller
{
    /**
     * Show the company settings form.
     */
    public function edit(): Response
    {
        $this->authorize('view', CompanySetting::class);

        return Inertia::render('settings/company', [
            'company' => CompanySetting::firstOrFail(),
        ]);
    }

    /**
     * Get the storage disk to use.
     */
    protected function getStorageDisk(): string
    {
        return config('filesystems.default', 'public');
    }

    /**
     * Update the company settings.
     */
    public function update(UpdateCompanySettingRequest $request)
    {
        $company = CompanySetting::firstOrFail();

        $data = $request->validated();
        $disk = $this->getStorageDisk();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo_path) {
                Storage::disk($disk)->delete($company->logo_path);
            }

            // Store new logo
            $data['logo_path'] = $request->file('logo')->store('company', $disk);
        }

        // Remove the logo key from data (we use logo_path)
        unset($data['logo']);

        $company->update($data);

        // Invalidate cached company settings
        Cache::forget('company_settings');

        return back()->with('success', 'Configuración de la empresa actualizada correctamente.');
    }

    /**
     * Delete the company logo.
     */
    public function deleteLogo()
    {
        $this->authorize('update', CompanySetting::class);

        $company = CompanySetting::firstOrFail();
        $disk = $this->getStorageDisk();

        if ($company->logo_path) {
            Storage::disk($disk)->delete($company->logo_path);
            $company->update(['logo_path' => null]);

            // Invalidate cached company settings
            Cache::forget('company_settings');
        }

        return back()->with('success', 'Logo eliminado correctamente.');
    }
}
