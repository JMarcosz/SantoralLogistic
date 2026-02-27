<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Display audit logs listing.
     */
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->module, fn($q, $module) => $q->where('module', $module))
            ->when($request->action, fn($q, $action) => $q->where('action', $action))
            ->when($request->user_id, fn($q, $userId) => $q->where('user_id', $userId))
            ->when($request->from_date, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('entity_label', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        // Get filter options
        $modules = [
            ['value' => AuditLog::MODULE_JOURNAL_ENTRIES, 'label' => 'Asientos Contables'],
            ['value' => AuditLog::MODULE_SETTINGS, 'label' => 'Configuración'],
            ['value' => AuditLog::MODULE_PERIODS, 'label' => 'Períodos'],
            ['value' => AuditLog::MODULE_ACCOUNTS, 'label' => 'Cuentas'],
            ['value' => AuditLog::MODULE_PAYMENTS, 'label' => 'Pagos'],
            ['value' => AuditLog::MODULE_BANK_RECONCILIATION, 'label' => 'Conciliación'],
        ];

        $actions = [
            ['value' => AuditLog::ACTION_CREATED, 'label' => 'Creado'],
            ['value' => AuditLog::ACTION_UPDATED, 'label' => 'Actualizado'],
            ['value' => AuditLog::ACTION_DELETED, 'label' => 'Eliminado'],
            ['value' => AuditLog::ACTION_POSTED, 'label' => 'Contabilizado'],
            ['value' => AuditLog::ACTION_REVERSED, 'label' => 'Reversado'],
            ['value' => AuditLog::ACTION_CLOSED, 'label' => 'Cerrado'],
            ['value' => AuditLog::ACTION_REOPENED, 'label' => 'Reabierto'],
            ['value' => AuditLog::ACTION_VOIDED, 'label' => 'Anulado'],
        ];

        $users = User::whereIn('id', AuditLog::select('user_id')->distinct())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('accounting/audit-logs/Index', [
            'logs' => $logs,
            'filterOptions' => [
                'modules' => $modules,
                'actions' => $actions,
                'users' => $users->map(fn($u) => ['value' => $u->id, 'label' => $u->name]),
            ],
            'filters' => $request->only(['module', 'action', 'user_id', 'from_date', 'to_date', 'search']),
        ]);
    }

    /**
     * Show details of a specific log entry.
     */
    public function show(AuditLog $auditLog): Response
    {
        $auditLog->load('user');

        // Get related logs for the same entity
        $entityHistory = AuditLog::forEntity($auditLog->entity_type, $auditLog->entity_id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return Inertia::render('accounting/audit-logs/Show', [
            'log' => $auditLog,
            'entityHistory' => $entityHistory,
        ]);
    }

    /**
     * Get entity history (AJAX).
     */
    public function entityHistory(Request $request)
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
        ]);

        $logs = AuditLog::forEntity($request->entity_type, $request->entity_id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    /**
     * Export audit logs.
     */
    public function export(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->module, fn($q, $module) => $q->where('module', $module))
            ->when($request->action, fn($q, $action) => $q->where('action', $action))
            ->when($request->from_date, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['Fecha', 'Usuario', 'Acción', 'Módulo', 'Entidad', 'Descripción', 'IP']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name ?? 'Sistema',
                    $log->action_label,
                    $log->module_label,
                    $log->entity_label,
                    $log->description,
                    $log->ip_address,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
