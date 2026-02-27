<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountingSetting Model
 * 
 * Singleton table for default GL accounts used in automatic journal posting.
 */
class AccountingSetting extends Model
{
    protected $fillable = [
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

    /**
     * Get the singleton instance.
     */
    public static function instance(): self
    {
        return static::firstOrCreate([]);
    }

    // ========== Relationships ==========

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ar_account_id');
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ap_account_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function discountAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'discount_account_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cash_account_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function exchangeGainAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'exchange_gain_account_id');
    }

    public function exchangeLossAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'exchange_loss_account_id');
    }

    public function isrRetentionAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'isr_retention_account_id');
    }

    public function itbisRetentionAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'itbis_retention_account_id');
    }

    // ========== Helper Methods ==========

    /**
     * Get account by type.
     */
    public function getAccount(string $type): ?Account
    {
        $relation = $type . 'Account';
        if (method_exists($this, $relation)) {
            return $this->$relation;
        }
        return null;
    }

    /**
     * Check if all required accounts are configured.
     */
    public function isConfigured(): bool
    {
        return $this->ar_account_id !== null
            && $this->revenue_account_id !== null;
    }

    /**
     * Get configuration status with missing accounts.
     */
    public function getConfigurationStatus(): array
    {
        $required = [
            'ar_account_id' => 'Cuentas por Cobrar',
            'revenue_account_id' => 'Ingresos',
        ];

        $optional = [
            'ap_account_id' => 'Cuentas por Pagar',
            'cogs_account_id' => 'Costo de Ventas',
            'discount_account_id' => 'Descuentos',
            'inventory_account_id' => 'Inventario',
            'cash_account_id' => 'Caja',
            'bank_account_id' => 'Banco',
            'exchange_gain_account_id' => 'Ganancia Cambiaria',
            'exchange_loss_account_id' => 'Pérdida Cambiaria',
            'isr_retention_account_id' => 'Retención ISR',
            'itbis_retention_account_id' => 'Retención ITBIS',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if ($this->$field === null) {
                $missing[] = $label;
            }
        }

        return [
            'is_configured' => empty($missing),
            'missing_required' => $missing,
        ];
    }
}
