import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { can } from '@/utils/permissions';
import { Head, Link, router } from '@inertiajs/react';
import { Download, Eye, FileText, Printer } from 'lucide-react';
import { FormEvent, useState } from 'react';

type Customer = {
    id: number;
    name: string;
    fiscal_name: string | null;
};

type ShippingOrder = {
    id: number;
    so_number: string;
};

type Invoice = {
    id: number;
    number: string;
    ncf: string;
    ncf_type: string;
    customer_id: number;
    customer: Customer;
    shipping_order: ShippingOrder | null;
    issue_date: string;
    currency_code: string;
    total_amount: number;
    status: 'issued' | 'cancelled';
};

type PaginatedInvoices = {
    data: Invoice[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
};

type Props = {
    invoices: PaginatedInvoices;
    customers: Customer[];
    filters: {
        customer_id?: string;
        status?: string;
        from_date?: string;
        to_date?: string;
        search?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturación',
        href: '/invoices',
    },
    {
        title: 'Facturas Fiscales',
        href: '/invoices',
    },
];

export default function InvoicesIndex({ invoices, customers, filters }: Props) {
    const [localFilters, setLocalFilters] = useState(filters);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [isDownloading, setIsDownloading] = useState(false);

    const handleFilterChange = (key: string, value: string) => {
        setLocalFilters((prev) => ({ ...prev, [key]: value }));
    };

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/invoices',
            { ...localFilters },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const clearFilters = () => {
        setLocalFilters({});
        router.get('/invoices', {}, { preserveState: true, replace: true });
    };

    const goToPage = (page: number) => {
        router.get(
            '/invoices',
            { ...localFilters, page },
            { preserveState: true, replace: true },
        );
    };

    const formatCurrency = (amount: number, currency: string) => {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-DO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const toggleSelectAll = () => {
        if (selectedIds.length === invoices.data.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(invoices.data.map((inv) => inv.id));
        }
    };

    const toggleSelectInvoice = (id: number) => {
        setSelectedIds((prev) =>
            prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
        );
    };

    const handleBatchDownload = () => {
        if (selectedIds.length === 0) return;

        setIsDownloading(true);
        router.post(
            '/invoices/batch-export',
            { invoice_ids: selectedIds },
            {
                onFinish: () => {
                    setIsDownloading(false);
                    setSelectedIds([]);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facturas Fiscales" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">
                        Facturas Fiscales
                    </h1>
                    <p className="text-muted-foreground">
                        Gestione las facturas fiscales con NCF
                    </p>
                </div>

                {/* Batch Actions */}
                {selectedIds.length > 0 && can('invoices.export') && (
                    <Card className="border-primary bg-primary/5">
                        <CardContent className="flex items-center justify-between py-4">
                            <div className="text-sm font-medium">
                                {selectedIds.length} factura(s) seleccionada(s)
                            </div>
                            <Button
                                onClick={handleBatchDownload}
                                disabled={isDownloading}
                            >
                                <Download className="mr-2 h-4 w-4" />
                                {isDownloading
                                    ? 'Generando ZIP...'
                                    : 'Descargar Seleccionadas'}
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={applyFilters} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                                {/* Customer Filter */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Cliente
                                    </label>
                                    <Select
                                        value={localFilters.customer_id || ''}
                                        onValueChange={(value) =>
                                            handleFilterChange(
                                                'customer_id',
                                                value,
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
                                            {customers.map((customer) => (
                                                <SelectItem
                                                    key={customer.id}
                                                    value={customer.id.toString()}
                                                >
                                                    {customer.fiscal_name ||
                                                        customer.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Status Filter */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Estado
                                    </label>
                                    <Select
                                        value={localFilters.status || ''}
                                        onValueChange={(value) =>
                                            handleFilterChange('status', value)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Todos los estados" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                Todos los estados
                                            </SelectItem>
                                            <SelectItem value="issued">
                                                Emitida
                                            </SelectItem>
                                            <SelectItem value="cancelled">
                                                Cancelada
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* From Date */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Desde
                                    </label>
                                    <Input
                                        type="date"
                                        value={localFilters.from_date || ''}
                                        onChange={(e) =>
                                            handleFilterChange(
                                                'from_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>

                                {/* To Date */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Hasta
                                    </label>
                                    <Input
                                        type="date"
                                        value={localFilters.to_date || ''}
                                        onChange={(e) =>
                                            handleFilterChange(
                                                'to_date',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>

                                {/* Search */}
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Buscar
                                    </label>
                                    <Input
                                        placeholder="NCF, Número o SO..."
                                        value={localFilters.search || ''}
                                        onChange={(e) =>
                                            handleFilterChange(
                                                'search',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            {/* Filter Actions */}
                            <div className="flex gap-2">
                                <Button type="submit">Aplicar Filtros</Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={clearFilters}
                                >
                                    Limpiar
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                <div className="shadow-premium-sm overflow-hidden rounded-xl border border-border/50 bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12">
                                    <Checkbox
                                        checked={
                                            selectedIds.length ===
                                                invoices.data.length &&
                                            invoices.data.length > 0
                                        }
                                        onCheckedChange={toggleSelectAll}
                                    />
                                </TableHead>
                                <TableHead>NCF</TableHead>
                                <TableHead>Número</TableHead>
                                <TableHead>Cliente</TableHead>
                                <TableHead>SO</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Moneda</TableHead>
                                <TableHead className="text-right">
                                    Total
                                </TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">
                                    Acciones
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {invoices.data.length > 0 ? (
                                invoices.data.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell>
                                            <Checkbox
                                                checked={selectedIds.includes(
                                                    invoice.id,
                                                )}
                                                onCheckedChange={() =>
                                                    toggleSelectInvoice(
                                                        invoice.id,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="font-mono">
                                            {invoice.ncf}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {invoice.number}
                                        </TableCell>
                                        <TableCell>
                                            {invoice.customer.fiscal_name ||
                                                invoice.customer.name}
                                        </TableCell>
                                        <TableCell>
                                            {invoice.shipping_order
                                                ?.so_number || '-'}
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(invoice.issue_date)}
                                        </TableCell>
                                        <TableCell>
                                            {invoice.currency_code}
                                        </TableCell>
                                        <TableCell className="text-right font-mono">
                                            {formatCurrency(
                                                invoice.total_amount,
                                                invoice.currency_code,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    invoice.status === 'issued'
                                                        ? 'default'
                                                        : 'destructive'
                                                }
                                            >
                                                {invoice.status === 'issued'
                                                    ? 'Emitida'
                                                    : 'Cancelada'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Link
                                                    href={`/invoices/${invoice.id}`}
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                {can('invoices.print') && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            window.open(
                                                                `/invoices/${invoice.id}/print`,
                                                                '_blank',
                                                            )
                                                        }
                                                    >
                                                        <Printer className="h-4 w-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={10}
                                        className="h-24 text-center"
                                    >
                                        <div className="flex flex-col items-center justify-center gap-2">
                                            <FileText className="h-8 w-8 text-muted-foreground" />
                                            <p className="text-muted-foreground">
                                                No hay facturas para los filtros
                                                seleccionados
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                {invoices.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <div className="text-sm text-muted-foreground">
                            Mostrando {invoices.from} a {invoices.to} de{' '}
                            {invoices.total} facturas
                        </div>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    goToPage(invoices.current_page - 1)
                                }
                                disabled={invoices.current_page === 1}
                            >
                                Anterior
                            </Button>
                            <div className="flex items-center gap-1">
                                {Array.from(
                                    { length: invoices.last_page },
                                    (_, i) => i + 1,
                                )
                                    .filter(
                                        (page) =>
                                            page === 1 ||
                                            page === invoices.last_page ||
                                            Math.abs(
                                                page - invoices.current_page,
                                            ) <= 1,
                                    )
                                    .map((page, index, array) => (
                                        <>
                                            {index > 0 &&
                                                array[index - 1] !==
                                                    page - 1 && (
                                                    <span
                                                        key={`ellipsis-${page}`}
                                                        className="px-2"
                                                    >
                                                        ...
                                                    </span>
                                                )}
                                            <Button
                                                key={page}
                                                variant={
                                                    page ===
                                                    invoices.current_page
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                onClick={() => goToPage(page)}
                                            >
                                                {page}
                                            </Button>
                                        </>
                                    ))}
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    goToPage(invoices.current_page + 1)
                                }
                                disabled={
                                    invoices.current_page === invoices.last_page
                                }
                            >
                                Siguiente
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
