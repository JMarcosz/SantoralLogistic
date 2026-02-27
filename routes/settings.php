<?php

use App\Http\Controllers\Settings\CompanySettingController;
use App\Http\Controllers\Settings\CurrencyController;
use App\Http\Controllers\Settings\LocationController;
use App\Http\Controllers\Settings\PackageTypeController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\PortController;
use App\Http\Controllers\Settings\ProductServiceController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RateController;
use App\Http\Controllers\Settings\ServiceTypeController;
use App\Http\Controllers\Settings\TransportModeController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\Settings\WarehouseController;
use App\Http\Controllers\Settings\DriverController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('settings', '/settings/profile');

Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

Route::put('settings/password', [PasswordController::class, 'update'])
    ->middleware('throttle:6,1')
    ->name('user-password.update');

Route::get('settings/appearance', function () {
    return Inertia::render('settings/appearance');
})->name('appearance.edit');

Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
    ->name('two-factor.show');

// Company Settings
Route::get('settings/company', [CompanySettingController::class, 'edit'])->name('company.edit');
Route::post('settings/company', [CompanySettingController::class, 'update'])->name('company.update');
Route::delete('settings/company/logo', [CompanySettingController::class, 'deleteLogo'])->name('company.logo.delete');

// Currency Settings
Route::get('settings/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
Route::post('settings/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
Route::put('settings/currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
Route::delete('settings/currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');

// Port Settings
Route::get('settings/ports', [PortController::class, 'index'])->name('ports.index');
Route::post('settings/ports', [PortController::class, 'store'])->name('ports.store');
Route::put('settings/ports/{port}', [PortController::class, 'update'])->name('ports.update');
Route::delete('settings/ports/{port}', [PortController::class, 'destroy'])->name('ports.destroy');

// Service Type Settings
Route::get('settings/service-types', [ServiceTypeController::class, 'index'])->name('service-types.index');
Route::post('settings/service-types', [ServiceTypeController::class, 'store'])->name('service-types.store');
Route::put('settings/service-types/{service_type}', [ServiceTypeController::class, 'update'])->name('service-types.update');
Route::delete('settings/service-types/{service_type}', [ServiceTypeController::class, 'destroy'])->name('service-types.destroy');

// Package Type Settings
Route::get('settings/package-types', [PackageTypeController::class, 'index'])->name('package-types.index');
Route::post('settings/package-types', [PackageTypeController::class, 'store'])->name('package-types.store');
Route::put('settings/package-types/{package_type}', [PackageTypeController::class, 'update'])->name('package-types.update');
Route::delete('settings/package-types/{package_type}', [PackageTypeController::class, 'destroy'])->name('package-types.destroy');

// Transport Mode Settings
Route::get('settings/transport-modes', [TransportModeController::class, 'index'])->name('transport-modes.index');
Route::post('settings/transport-modes', [TransportModeController::class, 'store'])->name('transport-modes.store');
Route::put('settings/transport-modes/{transport_mode}', [TransportModeController::class, 'update'])->name('transport-modes.update');
Route::delete('settings/transport-modes/{transport_mode}', [TransportModeController::class, 'destroy'])->name('transport-modes.destroy');

// Products & Services Settings
Route::get('settings/products-services', [ProductServiceController::class, 'index'])->name('products-services.index');
Route::post('settings/products-services', [ProductServiceController::class, 'store'])->name('products-services.store');
Route::put('settings/products-services/{products_service}', [ProductServiceController::class, 'update'])->name('products-services.update');
Route::delete('settings/products-services/{products_service}', [ProductServiceController::class, 'destroy'])->name('products-services.destroy');

// Rates Settings
Route::get('settings/rates', [RateController::class, 'index'])->name('rates.index');
Route::post('settings/rates', [RateController::class, 'store'])->name('rates.store');
Route::put('settings/rates/{rate}', [RateController::class, 'update'])->name('rates.update');
Route::delete('settings/rates/{rate}', [RateController::class, 'destroy'])->name('rates.destroy');

// Terms Settings
Route::get('settings/terms', [App\Http\Controllers\Settings\TermController::class, 'index'])->name('terms.index');
Route::post('settings/terms', [App\Http\Controllers\Settings\TermController::class, 'store'])->name('terms.store');
Route::put('settings/terms/{term}', [App\Http\Controllers\Settings\TermController::class, 'update'])->name('terms.update');
Route::delete('settings/terms/{term}', [App\Http\Controllers\Settings\TermController::class, 'destroy'])->name('terms.destroy');

// Warehouse Settings
Route::get('settings/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
Route::post('settings/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
Route::put('settings/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
Route::delete('settings/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

// Location Settings
Route::get('settings/locations', [LocationController::class, 'index'])->name('locations.index');
Route::post('settings/locations', [LocationController::class, 'store'])->name('locations.store');
Route::put('settings/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
Route::delete('settings/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

// Driver Settings
Route::get('settings/drivers', [DriverController::class, 'index'])->name('settings.drivers.index');
Route::post('settings/drivers', [DriverController::class, 'store'])->name('settings.drivers.store');
Route::put('settings/drivers/{driver}', [DriverController::class, 'update'])->name('settings.drivers.update');
Route::post('settings/drivers/{driver}/toggle-active', [DriverController::class, 'toggleActive'])->name('settings.drivers.toggle-active');
Route::delete('settings/drivers/{driver}', [DriverController::class, 'destroy'])->name('settings.drivers.destroy');
