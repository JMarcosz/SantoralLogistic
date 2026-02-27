import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { ArrowLeft, Download, PieChart, Printer } from 'lucide-react';
import { useState } from 'react';

interface AccountNode {
    id: number;
    code: string;
    name: string;
    balance: number;
    level?: number;
    is_parent?: boolean;
}

interface AccountGroup {
    accounts: AccountNode[];
    total: number;
    retained_earnings?: number;
}

interface BalanceSheetReport {
    as_of_date: string;
    assets: AccountGroup;
    liabilities: AccountGroup;
    equity: AccountGroup;
    total_liabilities_equity: number;
    is_balanced: boolean;
}

interface Props {
    report: BalanceSheetReport;
    filters: {
        as_of_date: string;
        include_zero_balances: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Reportes', href: '/accounting/reports' },
    { title: 'Balance General', href: '/accounting/reports/balance-sheet' },
];

export default function BalanceSheet({ report, filters }: Props) {
    const [date, setDate] = useState(
        filters?.as_of_date || new Date().toISOString().split('T')[0],
    );

    const handleDateChange = () => {
        router.get(
            '/accounting/reports/balance-sheet',
            { as_of_date: date },
            { preserveState: true },
        );
    };

    const formatAmount = (amount: number) => {
        return new Intl.NumberFormat('es-DO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    };

    const handleExport = (type: 'csv' | 'pdf') => {
        window.location.href = `/accounting/reports/balance-sheet/export?as_of_date=${date}&format=${type}`;
    };

    const renderAccountTree = (accounts: AccountNode[], level = 0) => {
        return accounts.map((account) => (
            <div key={account.id}>
                <div
                    className={`flex justify-between py-2 ${
                        level === 0
                            ? 'font-semibold text-white'
                            : 'text-slate-300'
                    }`}
                    style={{ paddingLeft: `${level * 1.5}rem` }}
                >
                    <span>
                        {account.code} {account.name}
                    </span>
                    <span className="font-mono">
                        {account.children && account.children.length > 0
                            ? ''
                            : formatAmount(account.balance)}
                    </span>
                </div>
                {account.children && account.children.length > 0 && (
                    <>
                        {renderAccountTree(account.children, level + 1)}
                        <div
                            className="flex justify-between border-t border-white/10 py-2 font-medium text-white"
                            style={{ paddingLeft: `${level * 1.5}rem` }}
                        >
                            <span>Total {account.name}</span>
                            <span className="font-mono">
                                {formatAmount(account.balance)}
                            </span>
                        </div>
                    </>
                )}
            </div>
        ));
    };

    // Use is_balanced from backend
    const isBalanced = report.is_balanced;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Balance General" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/accounting/reports">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10">
                                <PieChart className="h-5 w-5 text-blue-400" />
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-white">
                                    Balance General
                                </h1>
                                <p className="text-sm text-slate-400">
                                    Al{' '}
                                    {format(
                                        new Date(report.as_of_date),
                                        "d 'de' MMMM 'de' yyyy",
                                        { locale: es },
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                            <Input
                                type="date"
                                value={date}
                                onChange={(e) => setDate(e.target.value)}
                                className="w-40"
                            />
                            <Button
                                variant="outline"
                                onClick={handleDateChange}
                            >
                                Actualizar
                            </Button>
                        </div>
                        <Button
                            variant="outline"
                            onClick={() => handleExport('csv')}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            CSV
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => window.print()}
                        >
                            <Printer className="mr-2 h-4 w-4" />
                            Imprimir
                        </Button>
                    </div>
                </div>

                {/* Balance Check */}
                {!isBalanced && (
                    <Card className="border-red-500/30 bg-red-500/10">
                        <CardContent className="py-4">
                            <p className="text-red-300">
                                ⚠️ Advertencia: El balance no cuadra.
                                Diferencia:{' '}
                                {formatAmount(
                                    report.assets.total -
                                        report.total_liabilities_equity,
                                )}
                            </p>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Assets */}
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardHeader>
                            <CardTitle className="text-white">
                                ACTIVOS
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1">
                            {report.assets.accounts.length > 0 ? (
                                renderAccountTree(report.assets.accounts)
                            ) : (
                                <p className="text-slate-400">
                                    No hay cuentas de activo
                                </p>
                            )}
                            <div className="flex justify-between border-t-2 border-white/20 pt-3 text-lg font-bold text-white">
                                <span>TOTAL ACTIVOS</span>
                                <span className="font-mono">
                                    {formatAmount(report.assets.total)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Liabilities & Equity */}
                    <div className="space-y-6">
                        {/* Liabilities */}
                        <Card className="border-white/10 bg-slate-800/50">
                            <CardHeader>
                                <CardTitle className="text-white">
                                    PASIVOS
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-1">
                                {report.liabilities.accounts.length > 0 ? (
                                    renderAccountTree(
                                        report.liabilities.accounts,
                                    )
                                ) : (
                                    <p className="text-slate-400">
                                        No hay cuentas de pasivo
                                    </p>
                                )}
                                <div className="flex justify-between border-t-2 border-white/20 pt-3 font-bold text-white">
                                    <span>TOTAL PASIVOS</span>
                                    <span className="font-mono">
                                        {formatAmount(report.liabilities.total)}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Equity */}
                        <Card className="border-white/10 bg-slate-800/50">
                            <CardHeader>
                                <CardTitle className="text-white">
                                    PATRIMONIO
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-1">
                                {report.equity.accounts.length > 0 ? (
                                    renderAccountTree(report.equity.accounts)
                                ) : (
                                    <p className="text-slate-400">
                                        No hay cuentas de patrimonio
                                    </p>
                                )}
                                {report.equity.retained_earnings !== 0 && (
                                    <div className="flex justify-between py-2 text-slate-300">
                                        <span>Resultado del Ejercicio</span>
                                        <span className="font-mono">
                                            {formatAmount(
                                                report.equity
                                                    .retained_earnings || 0,
                                            )}
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between border-t-2 border-white/20 pt-3 font-bold text-white">
                                    <span>TOTAL PATRIMONIO</span>
                                    <span className="font-mono">
                                        {formatAmount(report.equity.total)}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Total Liabilities + Equity */}
                        <Card className="border-emerald-500/30 bg-emerald-500/10">
                            <CardContent className="py-4">
                                <div className="flex justify-between text-lg font-bold text-emerald-300">
                                    <span>TOTAL PASIVOS + PATRIMONIO</span>
                                    <span className="font-mono">
                                        {formatAmount(
                                            report.total_liabilities_equity,
                                        )}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
