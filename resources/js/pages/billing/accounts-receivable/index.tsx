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
import arRoutes from '@/routes/billing/ar';
import preInvoiceRoutes from '@/routes/pre-invoices';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    Clock,
    DollarSign,
    Download,
    FileText,
    Filter,
    TrendingDown,
    TrendingUp,
    X,
} from 'lucide-react';
import { useState } from 'react';

interface Customer {
    id: number;
    name: string;
    code: string;
}

interface Currency {
    id: number;
    code: string;
    symbol: string;
    name: string;
}

interface PreInvoice {
    id: number;
    number: string;
    customer: Customer;
    issue_date: string;
    due_date: string | null;
    total_amount: string;
    paid_amount: string;
    balance: string;
    currency_code: string;
    days_overdue: number;
    aging_bucket: string;
}

interface AgingBucket {
    count: number;
    total: number;
}

interface AgingSummaryByCurrency {
    [currency: string]: {
        current: AgingBucket;
        '1_30': AgingBucket;
        '31_60': AgingBucket;
        '61_90': AgingBucket;
        over_90: AgingBucket;
        grand_total: number;
        total_count: number;
    };
}

interface Kpis {
    [currency: string]: {
        total_receivable: number;
        overdue_amount: number;
        current_amount: number;
        invoice_count: number;
        overdue_count: number;
    };
}

interface PageProps {
    invoices: {
        data: PreInvoice[];
        links: { url: string | null; label: string; active: boolean }[];
        meta?: { current_page: number; last_page: number };
    };
    agingSummary: AgingSummaryByCurrency;
    kpis: Kpis;
    customers: Customer[];
    currencies: Currency[];
    filters: {
        customer_id?: string;
        currency_code?: string;
        aging_bucket?: string;
    };
    can: {
        recordPayment: boolean;
        approvePayment: boolean;
        voidPayment: boolean;
        export: boolean;
    };
}

const bucketLabels: Record<string, string> = {
    current: 'Al día',
    '1_30': '1-30 días',
    '31_60': '31-60 días',
    '61_90': '61-90 días',
    over_90: '+90 días',
};

const bucketColors: Record<string, string> = {
    current: 'bg-emerald-500',
    '1_30': 'bg-yellow-500',
    '31_60': 'bg-orange-500',
    '61_90': 'bg-red-500',
    over_90: 'bg-red-700',
};

function formatCurrency(amount: number | string, currency: string): string {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('es-DO', {
        style: 'currency',
        currency: currency || 'USD',
        minimumFractionDigits: 2,
    }).format(num);
}

function formatDate(date: string | null): string {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('es-DO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function AccountsReceivableIndex({
    invoices,
    agingSummary,
    kpis,
    customers,
    currencies,
    filters,
    can,
}: PageProps) {
    const [selectedCurrency, setSelectedCurrency] = useState<string>(
        filters.currency_code || Object.keys(kpis)[0] || 'USD',
    );

    const currentKpis = kpis[selectedCurrency] || {
        total_receivable: 0,
        overdue_amount: 0,
        current_amount: 0,
        invoice_count: 0,
        overdue_count: 0,
    };

    const handleFilter = (key: string, value: string | undefined) => {
        const newFilters = { ...filters, [key]: value || undefined };
        Object.keys(newFilters).forEach((k) => {
            if (!newFilters[k as keyof typeof newFilters]) {
                delete newFilters[k as keyof typeof newFilters];
            }
        });
        router.get(arRoutes.index().url, newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        router.get(arRoutes.index().url, {}, { preserveState: true });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (filters.customer_id) params.set('customer_id', filters.customer_id);
        if (filters.currency_code)
            params.set('currency_code', filters.currency_code);
        window.location.href = `${arRoutes.export().url}?${params.toString()}`;
    };

    const hasFilters =
        filters.customer_id || filters.currency_code || filters.aging_bucket;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Cuentas por Cobrar', href: arRoutes.index().url },
            ]}
        >
            <Head title="Cuentas por Cobrar" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">
                            Cuentas por Cobrar
                        </h1>
                        <p className="text-muted-foreground">
                            Resumen de pre-facturas pendientes de cobro
                        </p>
                    </div>
                    {can.export && (
                        <Button onClick={handleExport} variant="outline">
                            <Download className="mr-2 h-4 w-4" />
                            Exportar CSV
                        </Button>
                    )}
                </div>

                {/* Currency Selector for KPIs */}
                <div className="flex items-center gap-4">
                    <span className="text-sm font-medium">
                        Ver resumen para:
                    </span>
                    <Select
                        value={selectedCurrency}
                        onValueChange={setSelectedCurrency}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.keys(kpis).map((code) => (
                                <SelectItem key={code} value={code}>
                                    {code}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* KPI Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Total por Cobrar
                            </CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(
                                    currentKpis.total_receivable,
                                    selectedCurrency,
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {currentKpis.invoice_count} facturas abiertas
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Al Día
                            </CardTitle>
                            <TrendingUp className="h-4 w-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-600">
                                {formatCurrency(
                                    currentKpis.current_amount,
                                    selectedCurrency,
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Sin vencer
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                Vencido
                            </CardTitle>
                            <TrendingDown className="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">
                                {formatCurrency(
                                    currentKpis.overdue_amount,
                                    selectedCurrency,
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {currentKpis.overdue_count} facturas vencidas
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">
                                % Vencido
                            </CardTitle>
                            <AlertTriangle className="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {currentKpis.total_receivable > 0
                                    ? (
                                          (currentKpis.overdue_amount /
                                              currentKpis.total_receivable) *
                                          100
                                      ).toFixed(1)
                                    : 0}
                                %
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Del total por cobrar
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Aging Summary by Currency */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Clock className="h-5 w-5" />
                            Aging por Moneda
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {Object.entries(agingSummary).map(
                                ([currency, buckets]) => (
                                    <div
                                        key={currency}
                                        className="rounded-lg border p-4"
                                    >
                                        <div className="mb-3 flex items-center justify-between">
                                            <h3 className="text-lg font-semibold">
                                                {currency}
                                            </h3>
                                            <span className="text-xl font-bold">
                                                {formatCurrency(
                                                    buckets.grand_total,
                                                    currency,
                                                )}
                                            </span>
                                        </div>
                                        <div className="grid gap-2 sm:grid-cols-5">
                                            {(
                                                [
                                                    'current',
                                                    '1_30',
                                                    '31_60',
                                                    '61_90',
                                                    'over_90',
                                                ] as const
                                            ).map((bucket) => (
                                                <button
                                                    key={bucket}
                                                    onClick={() =>
                                                        handleFilter(
                                                            'aging_bucket',
                                                            bucket,
                                                        )
                                                    }
                                                    className={`rounded-lg p-3 text-center transition-all hover:opacity-80 ${
                                                        filters.aging_bucket ===
                                                        bucket
                                                            ? 'ring-2 ring-primary'
                                                            : ''
                                                    } ${bucketColors[bucket]} text-white`}
                                                >
                                                    <div className="text-xs opacity-90">
                                                        {bucketLabels[bucket]}
                                                    </div>
                                                    <div className="text-lg font-bold">
                                                        {formatCurrency(
                                                            buckets[bucket]
                                                                .total,
                                                            currency,
                                                        )}
                                                    </div>
                                                    <div className="text-xs opacity-75">
                                                        {buckets[bucket].count}{' '}
                                                        fact.
                                                    </div>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                ),
                            )}
                            {Object.keys(agingSummary).length === 0 && (
                                <p className="py-8 text-center text-muted-foreground">
                                    No hay facturas pendientes
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Filter className="h-5 w-5" />
                            Filtros
                            {hasFilters && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearFilters}
                                    className="ml-2"
                                >
                                    <X className="mr-1 h-3 w-3" />
                                    Limpiar
                                </Button>
                            )}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label className="text-sm font-medium">
                                    Cliente
                                </label>
                                <Select
                                    value={filters.customer_id || ''}
                                    onValueChange={(v: string) =>
                                        handleFilter(
                                            'customer_id',
                                            v || undefined,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los clientes" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los clientes
                                        </SelectItem>
                                        {customers.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={String(c.id)}
                                            >
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="text-sm font-medium">
                                    Moneda
                                </label>
                                <Select
                                    value={filters.currency_code || ''}
                                    onValueChange={(v: string) =>
                                        handleFilter(
                                            'currency_code',
                                            v || undefined,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todas las monedas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todas las monedas
                                        </SelectItem>
                                        {currencies.map((c) => (
                                            <SelectItem
                                                key={c.code}
                                                value={c.code}
                                            >
                                                {c.code} ({c.symbol})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="text-sm font-medium">
                                    Aging
                                </label>
                                <Select
                                    value={filters.aging_bucket || ''}
                                    onValueChange={(v: string) =>
                                        handleFilter(
                                            'aging_bucket',
                                            v || undefined,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {Object.entries(bucketLabels).map(
                                            ([key, label]) => (
                                                <SelectItem
                                                    key={key}
                                                    value={key}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Invoices Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Facturas Pendientes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Número</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>Emisión</TableHead>
                                    <TableHead>Vencimiento</TableHead>
                                    <TableHead className="text-center">
                                        Días
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Pagado
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Saldo
                                    </TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="font-medium">
                                            <Link
                                                href={
                                                    preInvoiceRoutes.show(
                                                        invoice.id,
                                                    ).url
                                                }
                                                className="text-primary hover:underline"
                                            >
                                                {invoice.number}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            {invoice.customer?.name || 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(invoice.issue_date)}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(invoice.due_date)}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge
                                                variant={
                                                    invoice.days_overdue > 0
                                                        ? 'destructive'
                                                        : invoice.days_overdue ===
                                                            0
                                                          ? 'secondary'
                                                          : 'default'
                                                }
                                            >
                                                {invoice.days_overdue > 0
                                                    ? `+${invoice.days_overdue}`
                                                    : invoice.days_overdue}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                invoice.total_amount,
                                                invoice.currency_code,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            {formatCurrency(
                                                invoice.paid_amount,
                                                invoice.currency_code,
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-semibold">
                                            {formatCurrency(
                                                invoice.balance,
                                                invoice.currency_code,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={
                                                        preInvoiceRoutes.show(
                                                            invoice.id,
                                                        ).url
                                                    }
                                                >
                                                    Ver
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {invoices.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={9}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No hay facturas pendientes con los
                                            filtros seleccionados
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {invoices.links && invoices.links.length > 3 && (
                            <div className="mt-4 flex justify-center gap-1">
                                {invoices.links.map((link, i) => (
                                    <Button
                                        key={i}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() =>
                                            link.url &&
                                            router.get(link.url, filters, {
                                                preserveState: true,
                                            })
                                        }
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
