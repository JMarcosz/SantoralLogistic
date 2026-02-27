<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AuditLog Model
 * 
 * Records audit trail for accounting operations.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'entity_label',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Action constants
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_POSTED = 'posted';
    public const ACTION_REVERSED = 'reversed';
    public const ACTION_CLOSED = 'closed';
    public const ACTION_REOPENED = 'reopened';
    public const ACTION_VOIDED = 'voided';
    public const ACTION_RECONCILED = 'reconciled';

    // Module constants
    public const MODULE_JOURNAL_ENTRIES = 'journal_entries';
    public const MODULE_SETTINGS = 'settings';
    public const MODULE_PERIODS = 'periods';
    public const MODULE_ACCOUNTS = 'accounts';
    public const MODULE_PAYMENTS = 'payments';
    public const MODULE_BANK_RECONCILIATION = 'bank_reconciliation';

    // ========== Relationships ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    // ========== Scopes ==========

    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ========== Accessors ==========

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'Creado',
            self::ACTION_UPDATED => 'Actualizado',
            self::ACTION_DELETED => 'Eliminado',
            self::ACTION_POSTED => 'Contabilizado',
            self::ACTION_REVERSED => 'Reversado',
            self::ACTION_CLOSED => 'Cerrado',
            self::ACTION_REOPENED => 'Reabierto',
            self::ACTION_VOIDED => 'Anulado',
            self::ACTION_RECONCILED => 'Conciliado',
            default => ucfirst($this->action),
        };
    }

    public function getModuleLabelAttribute(): string
    {
        return match ($this->module) {
            self::MODULE_JOURNAL_ENTRIES => 'Asientos Contables',
            self::MODULE_SETTINGS => 'Configuración',
            self::MODULE_PERIODS => 'Períodos',
            self::MODULE_ACCOUNTS => 'Cuentas',
            self::MODULE_PAYMENTS => 'Pagos',
            self::MODULE_BANK_RECONCILIATION => 'Conciliación',
            default => ucfirst(str_replace('_', ' ', $this->module)),
        };
    }
}
