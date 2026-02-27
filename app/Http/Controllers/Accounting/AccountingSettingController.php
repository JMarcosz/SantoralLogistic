<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\AuditLog;
use App\Models\TaxMapping;
use App\Services\AccountingSettingService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class AccountingSettingController extends Controller
{
    public function __construct(
        protected AccountingSettingService $settingService,
        protected AuditService $auditService
    ) {}

    /**
     * Display the accounting settings page.
     */
    public function index(): Response
    {
        $settings = $this->settingService->get();

        // Get postable accounts for selectors
        $accounts = Account::active()
            ->postable()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'normal_balance']);

        // Get tax mappings
        $taxMappings = TaxMapping::with(['salesAccount:id,code,name', 'purchaseAccount:id,code,name'])
            ->orderBy('code')
            ->get();

        // Get default currency
        $baseCurrency = $this->settingService->getBaseCurrency();

        return Inertia::render('accounting/settings/index', [
            'settings' => $settings,
            'accounts' => $accounts,
            'taxMappings' => $taxMappings,
            'baseCurrency' => $baseCurrency,
            'configurationStatus' => $settings->getConfigurationStatus(),
        ]);
    }

    /**
     * Update accounting settings.
     */
    public function update(Request $request)
    {
        $accountFields = [
            'ar_account_id',
            'ap_account_id',
            'revenue_account_id',
            'cogs_account_id',
            'discount_account_id',
            'inventory_account_id',
            'cash_account_id',
            'bank_account_id',
            'exchange_gain_account_id',
            'exchange_loss_account_id',
            'isr_retention_account_id',
            'itbis_retention_account_id',
        ];

        $rules = [];
        foreach ($accountFields as $field) {
            $rules[$field] = 'nullable|integer|exists:accounts,id';
        }

        $validated = $request->validate($rules);

        try {
            $settings = $this->settingService->get();
            $oldValues = $settings->toArray();

            $this->settingService->update($validated);

            $this->auditService->logSettingsUpdated($settings->fresh(), $oldValues);

            return back()->with('success', 'Configuración contable actualizada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }
    }

    /**
     * Store a new tax mapping.
     */
    public function storeTaxMapping(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:tax_mappings,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rate' => 'required|numeric|min:0|max:100',
            'sales_account_id' => 'required|integer|exists:accounts,id',
            'purchase_account_id' => 'nullable|integer|exists:accounts,id',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // Validate accounts are postable
        $this->settingService->validatePostable($validated['sales_account_id']);
        if (!empty($validated['purchase_account_id'])) {
            $this->settingService->validatePostable($validated['purchase_account_id']);
        }

        $taxMapping = TaxMapping::create($validated);

        $this->auditService->logCreated(AuditLog::MODULE_SETTINGS, $taxMapping, 'Mapeo de impuesto creado: ' . $taxMapping->code);

        return back()->with('success', 'Mapeo de impuesto creado correctamente.');
    }

    /**
     * Update a tax mapping.
     */
    public function updateTaxMapping(Request $request, TaxMapping $taxMapping)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:tax_mappings,code,' . $taxMapping->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rate' => 'required|numeric|min:0|max:100',
            'sales_account_id' => 'required|integer|exists:accounts,id',
            'purchase_account_id' => 'nullable|integer|exists:accounts,id',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // Validate accounts are postable
        $this->settingService->validatePostable($validated['sales_account_id']);
        if (!empty($validated['purchase_account_id'])) {
            $this->settingService->validatePostable($validated['purchase_account_id']);
        }

        $oldValues = $taxMapping->toArray();

        $taxMapping->update($validated);

        $this->auditService->logUpdated(AuditLog::MODULE_SETTINGS, $taxMapping->fresh(), $oldValues, 'Mapeo de impuesto actualizado: ' . $taxMapping->code);

        return back()->with('success', 'Mapeo de impuesto actualizado correctamente.');
    }

    /**
     * Delete a tax mapping.
     */
    public function destroyTaxMapping(TaxMapping $taxMapping)
    {
        $this->auditService->logDeleted(AuditLog::MODULE_SETTINGS, $taxMapping, 'Mapeo de impuesto eliminado: ' . $taxMapping->code);

        $taxMapping->delete();

        return back()->with('success', 'Mapeo de impuesto eliminado correctamente.');
    }
}
