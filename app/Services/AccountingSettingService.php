<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing accounting settings.
 */
class AccountingSettingService
{
    protected const CACHE_KEY = 'accounting_settings';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the accounting settings instance.
     */
    public function get(): AccountingSetting
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return AccountingSetting::instance()->load([
                'arAccount',
                'apAccount',
                'revenueAccount',
                'cogsAccount',
                'discountAccount',
                'inventoryAccount',
                'cashAccount',
                'bankAccount',
                'exchangeGainAccount',
                'exchangeLossAccount',
                'isrRetentionAccount',
                'itbisRetentionAccount',
            ]);
        });
    }

    /**
     * Update accounting settings.
     */
    public function update(array $data): AccountingSetting
    {
        $settings = AccountingSetting::instance();

        // Validate all accounts are postable
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

        foreach ($accountFields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                $this->validatePostable((int) $data[$field]);
            }
        }

        $settings->update($data);

        // Clear cache
        Cache::forget(self::CACHE_KEY);

        return $settings->fresh()->load([
            'arAccount',
            'apAccount',
            'revenueAccount',
            'cogsAccount',
            'discountAccount',
            'inventoryAccount',
            'cashAccount',
            'bankAccount',
            'exchangeGainAccount',
            'exchangeLossAccount',
            'isrRetentionAccount',
            'itbisRetentionAccount',
        ]);
    }

    /**
     * Get a specific default account.
     */
    public function getAccount(string $type): ?Account
    {
        return $this->get()->getAccount($type);
    }

    /**
     * Validate that an account is postable.
     * 
     * @throws \InvalidArgumentException
     */
    public function validatePostable(int $accountId): bool
    {
        $account = Account::find($accountId);

        if (!$account) {
            throw new \InvalidArgumentException(
                "La cuenta con ID {$accountId} no existe."
            );
        }

        if (!$account->is_postable) {
            throw new \InvalidArgumentException(
                "La cuenta '{$account->code} - {$account->name}' no es posteable. Solo se permiten cuentas de detalle."
            );
        }

        if (!$account->is_active) {
            throw new \InvalidArgumentException(
                "La cuenta '{$account->code} - {$account->name}' no está activa."
            );
        }

        return true;
    }

    /**
     * Get the default currency from Currency model.
     */
    public function getBaseCurrency(): ?\App\Models\Currency
    {
        return \App\Models\Currency::where('is_default', true)->first();
    }
}
