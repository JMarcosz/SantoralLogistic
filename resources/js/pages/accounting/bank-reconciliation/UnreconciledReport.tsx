import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { AlertCircle, ArrowLeft, Download } from 'lucide-react';
import { useState } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
}

interface UnreconciledItem {
    id: number;
    date: string;
    description: string;
    reference: string | null;
    debit: number;
    credit: number;
    entry_number?: string;
}

interface Props {
    bankAccounts: Account[];
    selectedAccount: Account | null;
    report: UnreconciledItem[];
    filters: {
        account_id?: string;
        from_date?: string;
        to_date?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Conciliación Bancaria', href: '/accounting/bank-reconciliation' },
    {
        title: 'Partidas Pendientes',
        href: '/accounting/bank-reconciliation/unreconciled',
    },
];

export default function UnreconciledReport({
    bankAccounts,
    selectedAccount,
    report,
    filters,
}: Props) {
    const [accountId, setAccountId] = useState(filters.account_id || '');
    const [fromDate, setFromDate] = useState(filters.from_date || '');
    const [toDate, setToDate] = useState(filters.to_date || '');

    const formatAmount = (amount: number) => {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: 'DOP',
        }).format(amount);
    };

    const handleFilter = () => {
        router.get(
            '/accounting/bank-reconciliation/unreconciled',
            {
                account_id: accountId || undefined,
                from_date: fromDate || undefined,
                to_date: toDate || undefined,
            },
            { preserveState: true },
        );
    };

    const totalDebit = report.reduce((sum, item) => sum + item.debit, 0);
    const totalCredit = report.reduce((sum, item) => sum + item.credit, 0);
    const netBalance = totalDebit - totalCredit;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Partidas No Conciliadas" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/accounting/bank-reconciliation">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div className="flex items-center gap-2">
                            <AlertCircle className="h-6 w-6 text-amber-500" />
                            <div>
                                <h1 className="text-xl font-bold text-white">
                                    Partidas Pendientes de Conciliación
                                </h1>
                                <p className="text-sm text-slate-400">
                                    Movimientos del libro mayor sin conciliar
                                </p>
                            </div>
                        </div>
                    </div>
                    {report.length > 0 && (
                        <Button variant="outline">
                            <Download className="mr-2 h-4 w-4" />
                            Exportar
                        </Button>
                    )}
                </div>

                {/* Filters */}
                <Card className="border-white/10 bg-slate-800/50">
                    <CardContent className="pt-6">
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="w-64">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Cuenta Bancaria
                                </label>
                                <Select
                                    value={accountId}
                                    onValueChange={setAccountId}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione una cuenta" />
                                    </SelectTrigger>
                                    <SelectContent>
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
                            <div className="w-40">
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
                            <div className="w-40">
                                <label className="mb-1.5 block text-sm text-slate-400">
                                    Hasta
                                </label>
                                <Input
                                    type="date"
                                    value={toDate}
                                    onChange={(e) => setToDate(e.target.value)}
                                />
                            </div>
                            <Button onClick={handleFilter}>Filtrar</Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Results */}
                {selectedAccount && (
                    <>
                        {/* Summary */}
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Card className="border-white/10 bg-slate-800/50">
                                <CardContent className="pt-6">
                                    <p className="text-sm text-slate-400">
                                        Total Débitos
                                    </p>
                                    <p className="text-2xl font-bold text-emerald-400">
                                        {formatAmount(totalDebit)}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card className="border-white/10 bg-slate-800/50">
                                <CardContent className="pt-6">
                                    <p className="text-sm text-slate-400">
                                        Total Créditos
                                    </p>
                                    <p className="text-2xl font-bold text-red-400">
                                        {formatAmount(totalCredit)}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card className="border-white/10 bg-slate-800/50">
                                <CardContent className="pt-6">
                                    <p className="text-sm text-slate-400">
                                        Saldo Neto
                                    </p>
                                    <p
                                        className={`text-2xl font-bold ${netBalance >= 0 ? 'text-emerald-400' : 'text-red-400'}`}
                                    >
                                        {formatAmount(netBalance)}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Table */}
                        <Card className="border-white/10 bg-slate-800/50">
                            <CardHeader>
                                <CardTitle className="text-white">
                                    {selectedAccount.code} -{' '}
                                    {selectedAccount.name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow className="border-white/10">
                                            <TableHead className="text-slate-400">
                                                Asiento
                                            </TableHead>
                                            <TableHead className="text-slate-400">
                                                Fecha
                                            </TableHead>
                                            <TableHead className="text-slate-400">
                                                Descripción
                                            </TableHead>
                                            <TableHead className="text-right text-slate-400">
                                                Débito
                                            </TableHead>
                                            <TableHead className="text-right text-slate-400">
                                                Crédito
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {report.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={5}
                                                    className="h-24 text-center text-slate-400"
                                                >
                                                    No hay partidas pendientes
                                                    de conciliación
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            report.map((item) => (
                                                <TableRow
                                                    key={item.id}
                                                    className="border-white/10"
                                                >
                                                    <TableCell className="font-mono text-sky-400">
                                                        {item.entry_number}
                                                    </TableCell>
                                                    <TableCell className="text-white">
                                                        {format(
                                                            new Date(item.date),
                                                            'dd/MM/yyyy',
                                                            { locale: es },
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="max-w-xs truncate text-slate-300">
                                                        {item.description}
                                                    </TableCell>
                                                    <TableCell className="text-right font-mono text-white">
                                                        {item.debit > 0
                                                            ? formatAmount(
                                                                  item.debit,
                                                              )
                                                            : '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right font-mono text-white">
                                                        {item.credit > 0
                                                            ? formatAmount(
                                                                  item.credit,
                                                              )
                                                            : '-'}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </>
                )}

                {!selectedAccount && (
                    <Card className="border-amber-500/30 bg-amber-500/10">
                        <CardContent className="py-8 text-center">
                            <AlertCircle className="mx-auto mb-3 h-10 w-10 text-amber-400" />
                            <p className="text-lg font-medium text-amber-300">
                                Seleccione una cuenta bancaria para ver las
                                partidas pendientes
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
