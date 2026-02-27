import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { Building2, Eye, FileText, Plus } from 'lucide-react';
import { useState } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
}

interface User {
    id: number;
    name: string;
}

interface BankStatement {
    id: number;
    account_id: number;
    statement_date: string;
    period_start: string;
    period_end: string;
    reference: string | null;
    opening_balance: number;
    closing_balance: number;
    calculated_balance: number;
    status: 'draft' | 'in_progress' | 'completed';
    account?: Account;
    created_by?: User;
    lines_count?: number;
    reconciled_count?: number;
}

interface PaginatedData {
    data: BankStatement[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
}

interface Props {
    bankAccounts: Account[];
    statements: PaginatedData;
    filters: {
        account_id?: string;
    };
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/20 text-slate-300 border-slate-500/30',
    in_progress: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    completed: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    in_progress: 'En Progreso',
    completed: 'Completado',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Conciliación Bancaria', href: '/accounting/bank-reconciliation' },
];

export default function BankReconciliationIndex({
    bankAccounts,
    statements,
    filters,
}: Props) {
    const [accountId, setAccountId] = useState(filters.account_id || '');

    const handleFilter = (value: string) => {
        setAccountId(value);
        router.get(
            '/accounting/bank-reconciliation',
            { account_id: value === 'all' ? undefined : value },
            { preserveState: true },
        );
    };

    const formatAmount = (amount: number) => {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: 'DOP',
        }).format(amount);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Conciliación Bancaria" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-500/10">
                            <Building2 className="h-6 w-6 text-teal-500" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-white">
                                Conciliación Bancaria
                            </h1>
                            <p className="text-sm text-slate-400">
                                Concilie sus cuentas bancarias con los
                                movimientos del libro mayor
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/accounting/bank-reconciliation/unreconciled">
                                Partidas Pendientes
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/accounting/bank-reconciliation/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Estado de Cuenta
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Filter by Account */}
                <Card className="border-white/10 bg-slate-800/50">
                    <CardContent className="pt-6">
                        <div className="flex items-end gap-4">
                            <div className="w-72">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Filtrar por Cuenta
                                </label>
                                <Select
                                    value={accountId}
                                    onValueChange={handleFilter}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todas las cuentas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todas las cuentas
                                        </SelectItem>
                                        {bankAccounts.map((account) => (
                                            <SelectItem
                                                key={account.id}
                                                value={account.id.toString()}
                                            >
                                                {account.code} - {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Statements Table */}
                <div className="rounded-xl border border-white/10 bg-slate-800/50">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-white/10 hover:bg-white/5">
                                <TableHead className="text-slate-400">
                                    Cuenta
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Referencia
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Período
                                </TableHead>
                                <TableHead className="text-right text-slate-400">
                                    Saldo Inicial
                                </TableHead>
                                <TableHead className="text-right text-slate-400">
                                    Saldo Final
                                </TableHead>
                                <TableHead className="text-center text-slate-400">
                                    Estado
                                </TableHead>
                                <TableHead className="text-center text-slate-400">
                                    Acciones
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {statements.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-32 text-center text-slate-400"
                                    >
                                        <FileText className="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        No hay estados de cuenta registrados
                                    </TableCell>
                                </TableRow>
                            ) : (
                                statements.data.map((statement) => (
                                    <TableRow
                                        key={statement.id}
                                        className="border-white/10 hover:bg-white/5"
                                    >
                                        <TableCell className="text-white">
                                            {statement.account?.code} -{' '}
                                            {statement.account?.name}
                                        </TableCell>
                                        <TableCell className="font-mono text-sky-400">
                                            {statement.reference || '-'}
                                        </TableCell>
                                        <TableCell className="text-slate-300">
                                            {format(
                                                new Date(
                                                    statement.period_start,
                                                ),
                                                'dd/MM/yy',
                                                { locale: es },
                                            )}
                                            {' - '}
                                            {format(
                                                new Date(statement.period_end),
                                                'dd/MM/yy',
                                                { locale: es },
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-mono text-white">
                                            {formatAmount(
                                                statement.opening_balance,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-mono text-white">
                                            {formatAmount(
                                                statement.closing_balance,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge
                                                variant="outline"
                                                className={
                                                    statusColors[
                                                        statement.status
                                                    ]
                                                }
                                            >
                                                {statusLabels[statement.status]}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/accounting/bank-reconciliation/${statement.id}`}
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
                    {statements.last_page > 1 && (
                        <div className="flex items-center justify-center gap-2 border-t border-white/10 p-4">
                            {statements.links.map((link, i) => (
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
