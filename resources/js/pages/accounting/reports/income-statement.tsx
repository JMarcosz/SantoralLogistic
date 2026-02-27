import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { ArrowLeft, Download, Printer, TrendingUp } from 'lucide-react';
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
}

interface IncomeStatementReport {
    period: string;
    period_start: string;
    period_end: string;
    revenue: AccountGroup;
    expenses: AccountGroup;
    net_income: number;
    gross_margin: number;
    is_ytd: boolean;
}

interface Props {
    report: IncomeStatementReport;
    filters: {
        period: string;
        ytd: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Reportes', href: '/accounting/reports' },
    {
        title: 'Estado de Resultados',
        href: '/accounting/reports/income-statement',
    },
];

export default function IncomeStatement({ report, filters }: Props) {
    const [selectedPeriod, setSelectedPeriod] = useState(
        filters?.period || format(new Date(), 'yyyy-MM'),
    );
    const [isYtd, setIsYtd] = useState(filters?.ytd || false);

    const handleFilter = () => {
        router.get(
            '/accounting/reports/income-statement',
            { period: selectedPeriod, ytd: isYtd },
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
        window.location.href = `/accounting/reports/income-statement/export?period=${selectedPeriod}&ytd=${isYtd}&format=${type}`;
    };

    const renderAccountTree = (
        accounts: AccountNode[],
        level = 0,
    ): React.ReactNode => {
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

    // Generate period options (last 24 months)
    const periodOptions = [];
    const now = new Date();
    for (let i = 0; i < 24; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        periodOptions.push({
            value: format(d, 'yyyy-MM'),
            label: format(d, 'MMMM yyyy', { locale: es }),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Estado de Resultados" />

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
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10">
                                <TrendingUp className="h-5 w-5 text-emerald-400" />
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-white">
                                    Estado de Resultados
                                </h1>
                                <p className="text-sm text-slate-400">
                                    {isYtd ? 'Acumulado al ' : 'Período: '}
                                    {format(
                                        new Date(report.period_end),
                                        "d 'de' MMMM 'de' yyyy",
                                        { locale: es },
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Select
                            value={selectedPeriod}
                            onValueChange={setSelectedPeriod}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {periodOptions.map((opt) => (
                                    <SelectItem
                                        key={opt.value}
                                        value={opt.value}
                                    >
                                        {opt.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <label className="flex items-center gap-2 text-sm text-slate-300">
                            <input
                                type="checkbox"
                                checked={isYtd}
                                onChange={(e) => setIsYtd(e.target.checked)}
                                className="rounded"
                            />
                            YTD (Acumulado)
                        </label>
                        <Button variant="outline" onClick={handleFilter}>
                            Actualizar
                        </Button>
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

                <div className="mx-auto max-w-3xl space-y-6">
                    {/* Revenue */}
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardHeader>
                            <CardTitle className="text-white">
                                INGRESOS
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1">
                            {report.revenue.accounts.length > 0 ? (
                                renderAccountTree(report.revenue.accounts)
                            ) : (
                                <p className="text-slate-400">
                                    No hay ingresos registrados
                                </p>
                            )}
                            <div className="flex justify-between border-t-2 border-white/20 pt-3 text-lg font-bold text-emerald-400">
                                <span>TOTAL INGRESOS</span>
                                <span className="font-mono">
                                    {formatAmount(report.revenue.total)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Expenses */}
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardHeader>
                            <CardTitle className="text-white">GASTOS</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1">
                            {report.expenses.accounts.length > 0 ? (
                                renderAccountTree(report.expenses.accounts)
                            ) : (
                                <p className="text-slate-400">
                                    No hay gastos registrados
                                </p>
                            )}
                            <div className="flex justify-between border-t-2 border-white/20 pt-3 text-lg font-bold text-red-400">
                                <span>TOTAL GASTOS</span>
                                <span className="font-mono">
                                    ({formatAmount(report.expenses.total)})
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Net Income */}
                    <Card
                        className={`border-2 ${report.net_income >= 0 ? 'border-emerald-500/50 bg-emerald-500/10' : 'border-red-500/50 bg-red-500/10'}`}
                    >
                        <CardContent className="py-6">
                            <div className="flex justify-between text-2xl font-bold">
                                <span
                                    className={
                                        report.net_income >= 0
                                            ? 'text-emerald-300'
                                            : 'text-red-300'
                                    }
                                >
                                    {report.net_income >= 0
                                        ? 'UTILIDAD NETA'
                                        : 'PÉRDIDA NETA'}
                                </span>
                                <span
                                    className={`font-mono ${report.net_income >= 0 ? 'text-emerald-300' : 'text-red-300'}`}
                                >
                                    {formatAmount(Math.abs(report.net_income))}
                                </span>
                            </div>
                            <p className="mt-2 text-sm text-slate-400">
                                Ingresos ({formatAmount(report.revenue.total)})
                                - Gastos ({formatAmount(report.expenses.total)})
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
