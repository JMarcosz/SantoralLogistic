import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import {
    ArrowLeft,
    DollarSign,
    FileText,
    TrendingUp,
    XCircle,
} from 'lucide-react';
import { FormEvent, useState } from 'react';

type StatusData = {
    status: string;
    count: number;
    total: number;
};

type CustomerData = {
    customer_name: string;
    count: number;
    total: number;
};

type NcfTypeData = {
    ncf_type: string;
    count: number;
};

type DailyRevenueData = {
    date: string;
    total: number;
};

type Props = {
    metrics: {
        total_invoices: number;
        total_revenue: number;
        cancelled_count: number;
        average_value: number;
    };
    charts: {
        by_status: StatusData[];
        by_customer: CustomerData[];
        by_ncf_type: NcfTypeData[];
        daily_revenue: DailyRevenueData[];
    };
    filters: {
        from_date: string;
        to_date: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturación',
        href: '/invoices',
    },
    {
        title: 'Analíticas',
        href: '/invoices/analytics',
    },
];

export default function InvoicesAnalytics({ metrics, charts, filters }: Props) {
    const [localFilters, setLocalFilters] = useState(filters);

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/invoices/analytics',
            { ...localFilters },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: 'DOP',
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-DO', {
            month: 'short',
            day: 'numeric',
        });
    };

    const maxRevenue = Math.max(...charts.daily_revenue.map((d) => d.total), 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analíticas de Facturas" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Analíticas de Facturas
                        </h1>
                        <p className="text-muted-foreground">
                            Métricas y reportes fiscales
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <a href="/invoices">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver al listado
                        </a>
                    </Button>
                </div>

                {/* Date Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Período</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={applyFilters} className="flex gap-4">
                            <div className="flex-1 space-y-2">
                                <label className="text-sm font-medium">
                                    Desde
                                </label>
                                <Input
                                    type="date"
                                    value={localFilters.from_date}
                                    onChange={(e) =>
                                        setLocalFilters((prev) => ({
                                            ...prev,
                                            from_date: e.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="flex-1 space-y-2">
                                <label className="text-sm font-medium">
                                    Hasta
                                </label>
                                <Input
                                    type="date"
                                    value={localFilters.to_date}
                                    onChange={(e) =>
                                        setLocalFilters((prev) => ({
                                            ...prev,
                                            to_date: e.target.value,
                                        }))
                                    }
                                />
                            </div>
                            <div className="flex items-end">
                                <Button type="submit">Aplicar</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Metric Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total Facturas
                            </CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {metrics.total_invoices}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Ingresos Totales
                            </CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(metrics.total_revenue)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Promedio por Factura
                            </CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(metrics.average_value)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Canceladas
                            </CardTitle>
                            <XCircle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {metrics.cancelled_count}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {metrics.total_invoices > 0
                                    ? (
                                          (metrics.cancelled_count /
                                              metrics.total_invoices) *
                                          100
                                      ).toFixed(1)
                                    : 0}
                                % del total
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Revenue Trend Chart */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Ingresos Diarios</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {charts.daily_revenue.map((item) => (
                                    <div
                                        key={item.date}
                                        className="flex items-center gap-2"
                                    >
                                        <div className="w-20 text-xs text-muted-foreground">
                                            {formatDate(item.date)}
                                        </div>
                                        <div className="flex-1">
                                            <div
                                                className="flex h-8 items-center rounded bg-primary/20 px-2"
                                                style={{
                                                    width: `${(item.total / maxRevenue) * 100}%`,
                                                    minWidth: '60px',
                                                }}
                                            >
                                                <span className="text-xs font-medium">
                                                    {formatCurrency(item.total)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* By Status */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Por Estado</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {charts.by_status.map((item) => (
                                <div key={item.status} className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant={
                                                    item.status === 'issued'
                                                        ? 'default'
                                                        : 'destructive'
                                                }
                                            >
                                                {item.status === 'issued'
                                                    ? 'Emitidas'
                                                    : 'Canceladas'}
                                            </Badge>
                                            <span className="text-sm">
                                                {item.count} facturas
                                            </span>
                                        </div>
                                        <span className="font-mono text-sm font-semibold">
                                            {formatCurrency(item.total)}
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Top Customers */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Top 10 Clientes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {charts.by_customer
                                    .slice(0, 10)
                                    .map((item, index) => (
                                        <div
                                            key={index}
                                            className="flex items-center justify-between"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium">
                                                    {item.customer_name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {item.count} facturas
                                                </p>
                                            </div>
                                            <div className="font-mono text-sm font-semibold">
                                                {formatCurrency(item.total)}
                                            </div>
                                        </div>
                                    ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* By NCF Type */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Por Tipo de NCF</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {charts.by_ncf_type.map((item) => (
                                <div
                                    key={item.ncf_type}
                                    className="flex items-center justify-between"
                                >
                                    <span className="font-mono text-sm font-medium">
                                        {item.ncf_type}
                                    </span>
                                    <Badge variant="secondary">
                                        {item.count}
                                    </Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
