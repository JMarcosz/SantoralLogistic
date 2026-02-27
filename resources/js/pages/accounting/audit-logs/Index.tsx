import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Download, Eye, FileText, History, Search } from 'lucide-react';
import { useState } from 'react';

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
    description: string | null;
    created_at: string;
    user?: User;
}

interface PaginatedData {
    data: AuditLog[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Filters {
    module?: string;
    action?: string;
    user_id?: string;
    from_date?: string;
    to_date?: string;
    search?: string;
}

interface Props {
    logs: PaginatedData;
    filters: Filters;
    filterOptions: {
        modules: { value: string; label: string }[];
        actions: { value: string; label: string }[];
        users: { value: number; label: string }[];
    };
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Auditoría', href: '/accounting/audit-logs' },
];

export default function AuditLogsIndex({
    logs,
    filters,
    filterOptions,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [module, setModule] = useState(filters.module || '');
    const [action, setAction] = useState(filters.action || '');
    const [userId, setUserId] = useState(filters.user_id || '');
    const [fromDate, setFromDate] = useState(filters.from_date || '');
    const [toDate, setToDate] = useState(filters.to_date || '');

    const handleFilter = () => {
        router.get(
            '/accounting/audit-logs',
            {
                search: search || undefined,
                module: module || undefined,
                action: action || undefined,
                user_id: userId || undefined,
                from_date: fromDate || undefined,
                to_date: toDate || undefined,
            },
            { preserveState: true },
        );
    };

    const handleClear = () => {
        setSearch('');
        setModule('');
        setAction('');
        setUserId('');
        setFromDate('');
        setToDate('');
        router.get('/accounting/audit-logs');
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (module) params.set('module', module);
        if (action) params.set('action', action);
        if (userId) params.set('user_id', userId);
        if (fromDate) params.set('from_date', fromDate);
        if (toDate) params.set('to_date', toDate);

        window.location.href = `/accounting/audit-logs/export?${params.toString()}`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Auditoría Contable" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-500/10">
                            <History className="h-6 w-6 text-indigo-500" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-white">
                                Registro de Auditoría
                            </h1>
                            <p className="text-sm text-slate-400">
                                Historial de cambios en el módulo contable
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" onClick={handleExport}>
                        <Download className="mr-2 h-4 w-4" />
                        Exportar CSV
                    </Button>
                </div>

                {/* Filters */}
                <Card className="border-white/10 bg-slate-800/50">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="min-w-[200px] flex-1">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Buscar
                                </label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        placeholder="Descripción o entidad..."
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(e.target.value)
                                        }
                                        className="pl-10"
                                        onKeyDown={(e) =>
                                            e.key === 'Enter' && handleFilter()
                                        }
                                    />
                                </div>
                            </div>

                            <div className="w-40">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Módulo
                                </label>
                                <Select
                                    value={module}
                                    onValueChange={setModule}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {filterOptions.modules.map((m) => (
                                            <SelectItem
                                                key={m.value}
                                                value={m.value}
                                            >
                                                {m.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="w-40">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Acción
                                </label>
                                <Select
                                    value={action}
                                    onValueChange={setAction}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todas
                                        </SelectItem>
                                        {filterOptions.actions.map((a) => (
                                            <SelectItem
                                                key={a.value}
                                                value={a.value}
                                            >
                                                {a.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="w-36">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Desde
                                </label>
                                <Input
                                    type="date"
                                    value={fromDate}
                                    onChange={(e) =>
                                        setFromDate(e.target.value)
                                    }
                                />
                            </div>

                            <div className="w-36">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Hasta
                                </label>
                                <Input
                                    type="date"
                                    value={toDate}
                                    onChange={(e) => setToDate(e.target.value)}
                                />
                            </div>

                            <div className="flex gap-2">
                                <Button onClick={handleFilter}>Filtrar</Button>
                                <Button variant="outline" onClick={handleClear}>
                                    Limpiar
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Results Summary */}
                <div className="text-sm text-slate-400">
                    Mostrando {logs.data.length} de {logs.total} registros
                </div>

                {/* Table */}
                <div className="rounded-xl border border-white/10 bg-slate-800/50">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-white/10 hover:bg-white/5">
                                <TableHead className="text-slate-400">
                                    Fecha
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Usuario
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Módulo
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Acción
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Entidad
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Descripción
                                </TableHead>
                                <TableHead className="text-center text-slate-400">
                                    Ver
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-32 text-center text-slate-400"
                                    >
                                        <FileText className="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        No hay registros de auditoría
                                    </TableCell>
                                </TableRow>
                            ) : (
                                logs.data.map((log) => (
                                    <TableRow
                                        key={log.id}
                                        className="border-white/10 hover:bg-white/5"
                                    >
                                        <TableCell className="text-white">
                                            {format(
                                                new Date(log.created_at),
                                                'dd/MM/yyyy HH:mm',
                                                { locale: es },
                                            )}
                                        </TableCell>
                                        <TableCell className="text-slate-300">
                                            {log.user?.name ||
                                                log.user_name ||
                                                'Sistema'}
                                        </TableCell>
                                        <TableCell className="text-slate-300">
                                            {moduleLabels[log.module] ||
                                                log.module}
                                        </TableCell>
                                        <TableCell>
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
                                        </TableCell>
                                        <TableCell className="font-mono text-sky-400">
                                            {log.entity_label || '-'}
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate text-slate-400">
                                            {log.description || '-'}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/accounting/audit-logs/${log.id}`}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>

                    {/* Pagination */}
                    {logs.last_page > 1 && (
                        <div className="flex items-center justify-center gap-2 border-t border-white/10 p-4">
                            {logs.links.map((link, i) => (
                                <Button
                                    key={i}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    disabled={!link.url}
                                    onClick={() =>
                                        link.url && router.visit(link.url)
                                    }
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
