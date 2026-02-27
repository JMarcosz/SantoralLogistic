<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Service for creating audit log entries.
 */
class AuditService
{
    /**
     * Log an action on an entity.
     */
    public function log(
        string $action,
        string $module,
        Model $entity,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'module' => $module,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->getKey(),
            'entity_label' => $this->getEntityLabel($entity),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => substr(Request::userAgent() ?? '', 0, 255),
        ]);
    }

    /**
     * Log entity created.
     */
    public function logCreated(string $module, Model $entity, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CREATED,
            $module,
            $entity,
            $description ?? "Creado: {$this->getEntityLabel($entity)}",
            null,
            $entity->toArray()
        );
    }

    /**
     * Log entity updated.
     */
    public function logUpdated(string $module, Model $entity, array $oldValues, ?string $description = null): AuditLog
    {
        $changedValues = array_intersect_key($entity->toArray(), $oldValues);

        return $this->log(
            AuditLog::ACTION_UPDATED,
            $module,
            $entity,
            $description ?? "Actualizado: {$this->getEntityLabel($entity)}",
            $oldValues,
            $changedValues
        );
    }

    /**
     * Log entity deleted.
     */
    public function logDeleted(string $module, Model $entity, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_DELETED,
            $module,
            $entity,
            $description ?? "Eliminado: {$this->getEntityLabel($entity)}",
            $entity->toArray(),
            null
        );
    }

    /**
     * Log journal entry posted.
     */
    public function logPosted(Model $entity, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_POSTED,
            AuditLog::MODULE_JOURNAL_ENTRIES,
            $entity,
            $description ?? "Contabilizado: {$this->getEntityLabel($entity)}"
        );
    }

    /**
     * Log journal entry reversed.
     */
    public function logReversed(Model $originalEntry, Model $reversalEntry, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_REVERSED,
            AuditLog::MODULE_JOURNAL_ENTRIES,
            $originalEntry,
            $description ?? "Reversado: {$this->getEntityLabel($originalEntry)} → {$this->getEntityLabel($reversalEntry)}",
            null,
            ['reversal_entry_id' => $reversalEntry->getKey()]
        );
    }

    /**
     * Log period closed.
     */
    public function logPeriodClosed(Model $period, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_CLOSED,
            AuditLog::MODULE_PERIODS,
            $period,
            $description ?? "Período cerrado: {$this->getEntityLabel($period)}"
        );
    }

    /**
     * Log period reopened.
     */
    public function logPeriodReopened(Model $period, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_REOPENED,
            AuditLog::MODULE_PERIODS,
            $period,
            $description ?? "Período reabierto: {$this->getEntityLabel($period)}"
        );
    }

    /**
     * Log payment posted.
     */
    public function logPaymentPosted(Model $payment, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_POSTED,
            AuditLog::MODULE_PAYMENTS,
            $payment,
            $description ?? "Pago contabilizado: {$this->getEntityLabel($payment)}"
        );
    }

    /**
     * Log payment voided.
     */
    public function logPaymentVoided(Model $payment, ?string $description = null): AuditLog
    {
        return $this->log(
            AuditLog::ACTION_VOIDED,
            AuditLog::MODULE_PAYMENTS,
            $payment,
            $description ?? "Pago anulado: {$this->getEntityLabel($payment)}"
        );
    }

    /**
     * Log settings updated.
     */
    public function logSettingsUpdated(Model $settings, array $oldValues, ?string $description = null): AuditLog
    {
        return $this->logUpdated(
            AuditLog::MODULE_SETTINGS,
            $settings,
            $oldValues,
            $description ?? 'Configuración contable actualizada'
        );
    }

    /**
     * Get entity history.
     */
    public function getEntityHistory(Model $entity, int $limit = 50)
    {
        return AuditLog::forEntity(get_class($entity), $entity->getKey())
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get human-readable label for an entity.
     */
    protected function getEntityLabel(Model $entity): string
    {
        // Try common label attributes
        foreach (['entry_number', 'payment_number', 'number', 'name', 'display_name', 'code'] as $attr) {
            if ($entity->$attr) {
                return $entity->$attr;
            }
        }

        return class_basename($entity) . ' #' . $entity->getKey();
    }
}
