import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { ArrowLeft, Clock, FileText, User } from 'lucide-react';

interface User {
    id: number;
    name: string;
}

interface AuditLog {
    id: number;
    user_id: number | null;
    user_name: string | null;
    action: string;
    module: string;
    entity_type: string;
    entity_id: number | null;
    entity_label: string | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    description: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user?: User;
}

interface Props {
    log: AuditLog;
    entityHistory: AuditLog[];
}

const actionColors: Record<string, string> = {
    created: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
    updated: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
    deleted: 'bg-red-500/20 text-red-300 border-red-500/30',
    posted: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
    reversed: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    period_closed: 'bg-slate-500/20 text-slate-300 border-slate-500/30',
    period_reopened: 'bg-green-500/20 text-green-300 border-green-500/30',
};

const actionLabels: Record<string, string> = {
    created: 'Creado',
    updated: 'Actualizado',
    deleted: 'Eliminado',
    posted: 'Contabilizado',
    reversed: 'Reversado',
    period_closed: 'Período Cerrado',
    period_reopened: 'Período Reabierto',
};

const moduleLabels: Record<string, string> = {
    journal_entries: 'Asientos Contables',
    accounts: 'Plan de Cuentas',
    periods: 'Períodos',
    settings: 'Configuración',
    bank_reconciliation: 'Conciliación Bancaria',
    payments: 'Pagos',
    invoices: 'Facturas',
};

export default function AuditLogShow({ log, entityHistory }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Contabilidad', href: '/accounting' },
        { title: 'Auditoría', href: '/accounting/audit-logs' },
        { title: `Log #${log.id}`, href: `/accounting/audit-logs/${log.id}` },
    ];

    const renderValue = (value: unknown): string => {
        if (value === null || value === undefined) return '-';
        if (typeof value === 'boolean') return value ? 'Sí' : 'No';
        if (typeof value === 'object') return JSON.stringify(value, null, 2);
        return String(value);
    };

    const getChangedFields = () => {
        if (!log.old_values && !log.new_values) return [];

        const oldVals = log.old_values || {};
        const newVals = log.new_values || {};
        const allKeys = new Set([
            ...Object.keys(oldVals),
            ...Object.keys(newVals),
        ]);

        return Array.from(allKeys).filter((key) => {
            const oldVal = JSON.stringify(oldVals[key]);
            const newVal = JSON.stringify(newVals[key]);
            return oldVal !== newVal;
        });
    };

    const changedFields = getChangedFields();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Log de Auditoría #${log.id}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/accounting/audit-logs">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Info */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card className="border-white/10 bg-slate-800/50">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-white">
                                    <FileText className="h-5 w-5 text-indigo-400" />
                                    Detalle del Registro
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            Fecha y Hora
                                        </label>
                                        <p className="mt-1 text-white">
                                            {format(
                                                new Date(log.created_at),
                                                "dd 'de' MMMM yyyy, HH:mm:ss",
                                                { locale: es },
                                            )}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            Usuario
                                        </label>
                                        <p className="mt-1 flex items-center gap-2 text-white">
                                            <User className="h-4 w-4 text-slate-400" />
                                            {log.user?.name ||
                                                log.user_name ||
                                                'Sistema'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            Módulo
                                        </label>
                                        <p className="mt-1 text-white">
                                            {moduleLabels[log.module] ||
                                                log.module}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            Acción
                                        </label>
                                        <div className="mt-1">
                                            <Badge
                                                variant="outline"
                                                className={
                                                    actionColors[log.action] ||
                                                    'bg-slate-500/20 text-slate-300'
                                                }
                                            >
                                                {actionLabels[log.action] ||
                                                    log.action}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            Entidad
                                        </label>
                                        <p className="mt-1 font-mono text-sky-400">
                                            {log.entity_label || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            ID de Entidad
                                        </label>
                                        <p className="mt-1 text-white">
                                            {log.entity_id || '-'}
                                        </p>
                                    </div>
                                </div>

                                {log.description && (
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            Descripción
                                        </label>
                                        <p className="mt-1 text-slate-300">
                                            {log.description}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Changes */}
                        {changedFields.length > 0 && (
                            <Card className="border-white/10 bg-slate-800/50">
                                <CardHeader>
                                    <CardTitle className="text-white">
                                        Cambios Realizados
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-white/10">
                                                    <th className="pb-3 text-left text-sm text-slate-400">
                                                        Campo
                                                    </th>
                                                    <th className="pb-3 text-left text-sm text-slate-400">
                                                        Valor Anterior
                                                    </th>
                                                    <th className="pb-3 text-left text-sm text-slate-400">
                                                        Valor Nuevo
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {changedFields.map((field) => (
                                                    <tr
                                                        key={field}
                                                        className="border-b border-white/5"
                                                    >
                                                        <td className="py-3 font-mono text-sm text-white">
                                                            {field}
                                                        </td>
                                                        <td className="py-3 text-sm text-red-400">
                                                            {renderValue(
                                                                log
                                                                    .old_values?.[
                                                                    field
                                                                ],
                                                            )}
                                                        </td>
                                                        <td className="py-3 text-sm text-emerald-400">
                                                            {renderValue(
                                                                log
                                                                    .new_values?.[
                                                                    field
                                                                ],
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Technical Details */}
                        <Card className="border-white/10 bg-slate-800/50">
                            <CardHeader>
                                <CardTitle className="text-white">
                                    Información Técnica
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <label className="text-sm text-slate-400">
                                        Tipo de Entidad
                                    </label>
                                    <p className="mt-1 font-mono text-xs text-slate-500">
                                        {log.entity_type}
                                    </p>
                                </div>
                                {log.ip_address && (
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            Dirección IP
                                        </label>
                                        <p className="mt-1 font-mono text-xs text-slate-500">
                                            {log.ip_address}
                                        </p>
                                    </div>
                                )}
                                {log.user_agent && (
                                    <div>
                                        <label className="text-sm text-slate-400">
                                            User Agent
                                        </label>
                                        <p className="mt-1 font-mono text-xs break-all text-slate-500">
                                            {log.user_agent}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Entity History */}
                    <div>
                        <Card className="border-white/10 bg-slate-800/50">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-white">
                                    <Clock className="h-5 w-5 text-purple-400" />
                                    Historial de la Entidad
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {entityHistory.length === 0 ? (
                                    <p className="text-center text-sm text-slate-400">
                                        No hay más registros para esta entidad
                                    </p>
                                ) : (
                                    <div className="space-y-3">
                                        {entityHistory.map((historyLog) => (
                                            <Link
                                                key={historyLog.id}
                                                href={`/accounting/audit-logs/${historyLog.id}`}
                                                className={`block rounded-lg border p-3 transition-colors hover:bg-white/5 ${
                                                    historyLog.id === log.id
                                                        ? 'border-indigo-500/50 bg-indigo-500/10'
                                                        : 'border-white/10'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <Badge
                                                        variant="outline"
                                                        className={
                                                            actionColors[
                                                                historyLog
                                                                    .action
                                                            ] ||
                                                            'bg-slate-500/20 text-slate-300'
                                                        }
                                                    >
                                                        {actionLabels[
                                                            historyLog.action
                                                        ] || historyLog.action}
                                                    </Badge>
                                                    <span className="text-xs text-slate-500">
                                                        {format(
                                                            new Date(
                                                                historyLog.created_at,
                                                            ),
                                                            'dd/MM/yy HH:mm',
                                                        )}
                                                    </span>
                                                </div>
                                                <p className="mt-2 text-sm text-slate-400">
                                                    {historyLog.user?.name ||
                                                        historyLog.user_name ||
                                                        'Sistema'}
                                                </p>
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
