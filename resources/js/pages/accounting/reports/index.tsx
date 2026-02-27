import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BarChart3, FileSpreadsheet, PieChart, TrendingUp } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Reportes Financieros', href: '/accounting/reports' },
];

interface ReportCard {
    title: string;
    description: string;
    href: string;
    icon: React.ReactNode;
    color: string;
}

const reports: ReportCard[] = [
    {
        title: 'Balance General',
        description:
            'Estado de situación financiera con activos, pasivos y patrimonio',
        href: '/accounting/reports/balance-sheet',
        icon: <PieChart className="h-8 w-8" />,
        color: 'bg-blue-500/10 text-blue-400',
    },
    {
        title: 'Estado de Resultados',
        description: 'Ingresos, gastos y utilidad neta del período',
        href: '/accounting/reports/income-statement',
        icon: <TrendingUp className="h-8 w-8" />,
        color: 'bg-emerald-500/10 text-emerald-400',
    },
    {
        title: 'Mayor General',
        description: 'Movimientos detallados por cuenta contable',
        href: '/accounting/ledger',
        icon: <FileSpreadsheet className="h-8 w-8" />,
        color: 'bg-purple-500/10 text-purple-400',
    },
    {
        title: 'Libro Diario',
        description: 'Visualización de todos los asientos contables',
        href: '/accounting/journal-entries',
        icon: <BarChart3 className="h-8 w-8" />,
        color: 'bg-amber-500/10 text-amber-400',
    },
];

export default function ReportsIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes Financieros" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-violet-500/10">
                        <BarChart3 className="h-6 w-6 text-violet-500" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold text-white">
                            Reportes Financieros
                        </h1>
                        <p className="text-sm text-slate-400">
                            Estados financieros y reportes contables
                        </p>
                    </div>
                </div>

                {/* Report Cards */}
                <div className="grid gap-6 sm:grid-cols-2">
                    {reports.map((report) => (
                        <Link key={report.href} href={report.href}>
                            <Card className="group cursor-pointer border-white/10 bg-slate-800/50 transition-all hover:border-white/20 hover:bg-slate-800/70">
                                <CardHeader className="flex flex-row items-start gap-4">
                                    <div
                                        className={`rounded-lg p-3 ${report.color}`}
                                    >
                                        {report.icon}
                                    </div>
                                    <div className="flex-1">
                                        <CardTitle className="text-lg text-white transition-colors group-hover:text-sky-400">
                                            {report.title}
                                        </CardTitle>
                                        <p className="mt-1 text-sm text-slate-400">
                                            {report.description}
                                        </p>
                                    </div>
                                </CardHeader>
                            </Card>
                        </Link>
                    ))}
                </div>

                {/* Quick Info */}
                <Card className="border-slate-700 bg-slate-800/30">
                    <CardHeader>
                        <CardTitle className="text-white">
                            Información
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm text-slate-400">
                        <p>
                            •{' '}
                            <strong className="text-slate-300">
                                Balance General:
                            </strong>{' '}
                            Muestra la situación financiera a una fecha
                            específica. Activos = Pasivos + Patrimonio.
                        </p>
                        <p>
                            •{' '}
                            <strong className="text-slate-300">
                                Estado de Resultados:
                            </strong>{' '}
                            Presenta los ingresos y gastos de un período,
                            calculando la utilidad o pérdida neta.
                        </p>
                        <p>
                            • Los reportes pueden exportarse a CSV o PDF para su
                            distribución.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
