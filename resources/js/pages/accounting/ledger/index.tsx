import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import {
    ArrowDownCircle,
    ArrowUpCircle,
    Calculator,
    Download,
    FileSpreadsheet,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    normal_balance: 'debit' | 'credit';
}

interface Movement {
    id: number;
    date: string;
    entry_number: string;
    entry_id: number;
    description: string;
    debit: number;
    credit: number;
    balance: number;
}

interface Summary {
    account: Account | null;
    opening_balance: number;
    period_debit: number;
    period_credit: number;
    closing_balance: number;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    account_id: string | null;
    from: string;
    to: string;
}

interface Props {
    accounts: Account[];
    filters: Filters;
    summary: Summary | null;
    movements: Movement[];
    openingBalance: number;
    pagination: Pagination | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Libro Mayor', href: '/accounting/ledger' },
];

const accountTypeLabels: Record<string, string> = {
    asset: 'Activo',
    liability: 'Pasivo',
    equity: 'Capital',
    revenue: 'Ingreso',
    expense: 'Gasto',
};

const accountTypeColors: Record<string, string> = {
    asset: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
    liability: 'bg-red-500/20 text-red-300 border-red-500/30',
    equity: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
    revenue: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
    expense: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('es-DO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

export default function LedgerIndex({
    accounts,
    filters,
    summary,
    movements,
    openingBalance,
    pagination,
}: Props) {
    const [accountId, setAccountId] = useState(filters.account_id || '');
    const [dateFrom, setDateFrom] = useState(filters.from);
    const [dateTo, setDateTo] = useState(filters.to);

    const handleFilter = () => {
        if (!accountId) return;

        router.get(
            '/accounting/ledger',
            {
                account_id: accountId,
                from: dateFrom,
                to: dateTo,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleExport = () => {
        if (!accountId) return;
        window.location.href = `/accounting/ledger/export?account_id=${accountId}&from=${dateFrom}&to=${dateTo}`;
    };

    const handlePageChange = (page: number) => {
        router.get(
            '/accounting/ledger',
            {
                account_id: accountId,
                from: dateFrom,
                to: dateTo,
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const selectedAccount = accounts.find((a) => a.id === Number(accountId));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Libro Mayor" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Libro Mayor</h1>
                        <p className="text-muted-foreground">
                            Consulte movimientos y saldos por cuenta
                        </p>
                    </div>
                </div>

                {/* Filters */}
                <Card className="border-border/50 bg-card/50 backdrop-blur-sm">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="min-w-[300px] flex-1">
                                <label className="mb-2 block text-sm font-medium">
                                    Cuenta
                                </label>
                                <Select
                                    value={accountId}
                                    onValueChange={setAccountId}
                                >
                                    <SelectTrigger className="border-border/50 bg-background/50">
                                        <SelectValue placeholder="Seleccionar cuenta..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {accounts.map((account) => (
                                            <SelectItem
                                                key={account.id}
                                                value={String(account.id)}
                                            >
                                                <span className="font-mono text-muted-foreground">
                                                    {account.code}
                                                </span>{' '}
                                                - {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="w-[160px]">
                                <label className="mb-2 block text-sm font-medium">
                                    Desde
                                </label>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) =>
                                        setDateFrom(e.target.value)
                                    }
                                    className="flex h-9 w-full rounded-md border border-border/50 bg-background/50 px-3 py-1 text-sm shadow-sm"
                                />
                            </div>

                            <div className="w-[160px]">
                                <label className="mb-2 block text-sm font-medium">
                                    Hasta
                                </label>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                    className="flex h-9 w-full rounded-md border border-border/50 bg-background/50 px-3 py-1 text-sm shadow-sm"
                                />
                            </div>

                            <Button
                                onClick={handleFilter}
                                disabled={!accountId}
                            >
                                <Calculator className="mr-2 h-4 w-4" />
                                Consultar
                            </Button>

                            {summary && (
                                <Button
                                    variant="outline"
                                    onClick={handleExport}
                                >
                                    <Download className="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Account Info & Summary */}
                {summary && summary.account && (
                    <>
                        {/* Account Header */}
                        <Card className="border-border/50 bg-gradient-to-r from-primary/10 to-transparent">
                            <CardContent className="flex items-center gap-4 py-4">
                                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/20">
                                    <FileSpreadsheet className="h-6 w-6 text-primary" />
                                </div>
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono text-lg font-medium">
                                            {summary.account.code}
                                        </span>
                                        <span className="text-lg font-bold">
                                            {summary.account.name}
                                        </span>
                                        <Badge
                                            variant="outline"
                                            className={
                                                accountTypeColors[
                                                    summary.account.type
                                                ]
                                            }
                                        >
                                            {accountTypeLabels[
                                                summary.account.type
                                            ] || summary.account.type}
                                        </Badge>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Saldo normal:{' '}
                                        {summary.account.normal_balance ===
                                        'debit'
                                            ? 'Débito'
                                            : 'Crédito'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Balance Summary Cards */}
                        <div className="grid gap-4 md:grid-cols-4">
                            <Card className="border-border/50 bg-card/50">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        Saldo Inicial
                                    </CardTitle>
                                    <Wallet className="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <div
                                        className={`text-2xl font-bold ${summary.opening_balance >= 0 ? 'text-foreground' : 'text-red-400'}`}
                                    >
                                        {formatCurrency(
                                            summary.opening_balance,
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-border/50 bg-card/50">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        Débitos del Período
                                    </CardTitle>
                                    <ArrowUpCircle className="h-4 w-4 text-blue-400" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-blue-400">
                                        {formatCurrency(summary.period_debit)}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-border/50 bg-card/50">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        Créditos del Período
                                    </CardTitle>
                                    <ArrowDownCircle className="h-4 w-4 text-amber-400" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold text-amber-400">
                                        {formatCurrency(summary.period_credit)}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="border-emerald-500/30 bg-emerald-500/10">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">
                                        Saldo Final
                                    </CardTitle>
                                    <Calculator className="h-4 w-4 text-emerald-400" />
                                </CardHeader>
                                <CardContent>
                                    <div
                                        className={`text-2xl font-bold ${summary.closing_balance >= 0 ? 'text-emerald-400' : 'text-red-400'}`}
                                    >
                                        {formatCurrency(
                                            summary.closing_balance,
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </>
                )}

                {/* Movements Table */}
                {summary && (
                    <Card className="border-border/50 bg-card/50 backdrop-blur-sm">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileSpreadsheet className="h-5 w-5" />
                                Movimientos
                                {pagination && (
                                    <span className="text-sm font-normal text-muted-foreground">
                                        ({pagination.total} registros)
                                    </span>
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {movements.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                                    <FileSpreadsheet className="mb-4 h-12 w-12 opacity-50" />
                                    <p>
                                        No hay movimientos en el período
                                        seleccionado
                                    </p>
                                </div>
                            ) : (
                                <>
                                    <div className="rounded-md border border-border/50">
                                        <Table>
                                            <TableHeader>
                                                <TableRow className="bg-muted/30">
                                                    <TableHead className="w-[100px]">
                                                        Fecha
                                                    </TableHead>
                                                    <TableHead className="w-[140px]">
                                                        Asiento
                                                    </TableHead>
                                                    <TableHead>
                                                        Descripción
                                                    </TableHead>
                                                    <TableHead className="w-[130px] text-right">
                                                        Débito
                                                    </TableHead>
                                                    <TableHead className="w-[130px] text-right">
                                                        Crédito
                                                    </TableHead>
                                                    <TableHead className="w-[130px] text-right">
                                                        Saldo
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {/* Opening Balance Row */}
                                                <TableRow className="bg-muted/20">
                                                    <TableCell
                                                        colSpan={5}
                                                        className="font-medium"
                                                    >
                                                        Saldo Inicial
                                                    </TableCell>
                                                    <TableCell className="text-right font-bold">
                                                        {formatCurrency(
                                                            openingBalance,
                                                        )}
                                                    </TableCell>
                                                </TableRow>

                                                {movements.map((movement) => (
                                                    <TableRow
                                                        key={movement.id}
                                                        className="hover:bg-muted/10"
                                                    >
                                                        <TableCell className="font-mono text-sm">
                                                            {format(
                                                                new Date(
                                                                    movement.date,
                                                                ),
                                                                'dd/MM/yyyy',
                                                                { locale: es },
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Link
                                                                href={`/accounting/journal-entries/${movement.entry_id}`}
                                                                className="font-mono text-primary hover:underline"
                                                            >
                                                                {
                                                                    movement.entry_number
                                                                }
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell className="max-w-[300px] truncate">
                                                            {
                                                                movement.description
                                                            }
                                                        </TableCell>
                                                        <TableCell className="text-right font-mono">
                                                            {movement.debit > 0
                                                                ? formatCurrency(
                                                                      movement.debit,
                                                                  )
                                                                : '-'}
                                                        </TableCell>
                                                        <TableCell className="text-right font-mono">
                                                            {movement.credit > 0
                                                                ? formatCurrency(
                                                                      movement.credit,
                                                                  )
                                                                : '-'}
                                                        </TableCell>
                                                        <TableCell
                                                            className={`text-right font-mono font-medium ${movement.balance >= 0 ? '' : 'text-red-400'}`}
                                                        >
                                                            {formatCurrency(
                                                                movement.balance,
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    {/* Pagination */}
                                    {pagination && pagination.last_page > 1 && (
                                        <div className="mt-4 flex items-center justify-between">
                                            <p className="text-sm text-muted-foreground">
                                                Página {pagination.current_page}{' '}
                                                de {pagination.last_page}
                                            </p>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        handlePageChange(
                                                            pagination.current_page -
                                                                1,
                                                        )
                                                    }
                                                    disabled={
                                                        pagination.current_page ===
                                                        1
                                                    }
                                                >
                                                    Anterior
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        handlePageChange(
                                                            pagination.current_page +
                                                                1,
                                                        )
                                                    }
                                                    disabled={
                                                        pagination.current_page ===
                                                        pagination.last_page
                                                    }
                                                >
                                                    Siguiente
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Empty State */}
                {!summary && (
                    <Card className="border-border/50 bg-card/50">
                        <CardContent className="flex flex-col items-center justify-center py-16 text-muted-foreground">
                            <FileSpreadsheet className="mb-4 h-16 w-16 opacity-30" />
                            <h3 className="mb-2 text-lg font-medium">
                                Seleccione una cuenta
                            </h3>
                            <p className="text-center">
                                Elija una cuenta del catálogo y un rango de
                                fechas para ver sus movimientos y saldos.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
