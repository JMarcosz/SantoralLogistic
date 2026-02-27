import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatDate } from '@/lib/utils';
import preInvoiceRoutes from '@/routes/pre-invoices';
import { Head, Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Download,
    Eye,
    Loader2,
    MoreHorizontal,
    Plus,
    Printer,
    Search,
    X,
} from 'lucide-react';
import { useCallback, useState } from 'react';

interface PreInvoice {
    id: number;
    number: string;
    customer_id: number;
    shipping_order_id: number | null;
    status: 'draft' | 'issued' | 'cancelled';
    issue_date: string;
    due_date: string | null;
    currency_code: string;
    subtotal_amount: number;
    tax_amount: number;
    total_amount: number;
    customer?: {
        id: number;
        name: string;
        fiscal_name?: string;
    };
    shipping_order?: {
        id: number;
        order_number: string;
    };
}

interface Customer {
    id: number;
    name: string;
    code: string | null;
}

interface PaginatedPreInvoices {
    data: PreInvoice[];
    current_page: number;
    last_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    preInvoices: PaginatedPreInvoices;
    customers: Customer[];
    filters: {
        status?: string;
        customer_id?: string;
        from_date?: string;
        to_date?: string;
        search?: string;
    };
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    issued: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    issued: 'Emitida',
    cancelled: 'Cancelada',
};

export default function PreInvoicesIndex({
    preInvoices,
    customers,
    filters,
}: Props) {
    const [isLoading, setIsLoading] = useState(false);

    // Filter state
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [customerFilter, setCustomerFilter] = useState(
        filters.customer_id || 'all',
    );
    const [fromDate, setFromDate] = useState(filters.from_date || '');
    const [toDate, setToDate] = useState(filters.to_date || '');
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // Apply filters via Inertia
    const applyFilters = useCallback((newFilters: Record<string, string>) => {
        setIsLoading(true);
        const params: Record<string, string> = {};

        if (newFilters.status && newFilters.status !== 'all') {
            params.status = newFilters.status;
        }
        if (newFilters.customer_id && newFilters.customer_id !== 'all') {
            params.customer_id = newFilters.customer_id;
        }
        if (newFilters.from_date) {
            params.from_date = newFilters.from_date;
        }
        if (newFilters.to_date) {
            params.to_date = newFilters.to_date;
        }
        if (newFilters.search) {
            params.search = newFilters.search;
        }

        router.get(preInvoiceRoutes.index().url, params, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    }, []);

    const handleStatusChange = (value: string) => {
        setStatusFilter(value);
        applyFilters({
            status: value,
            customer_id: customerFilter,
            from_date: fromDate,
            to_date: toDate,
            search: searchTerm,
        });
    };

    const handleCustomerChange = (value: string) => {
        setCustomerFilter(value);
        applyFilters({
            status: statusFilter,
            customer_id: value,
            from_date: fromDate,
            to_date: toDate,
            search: searchTerm,
        });
    };

    const handleDateChange = (type: 'from' | 'to', value: string) => {
        if (type === 'from') {
            setFromDate(value);
        } else {
            setToDate(value);
        }
        applyFilters({
            status: statusFilter,
            customer_id: customerFilter,
            from_date: type === 'from' ? value : fromDate,
            to_date: type === 'to' ? value : toDate,
            search: searchTerm,
        });
    };

    const handleSearchChange = (value: string) => {
        setSearchTerm(value);
        applyFilters({
            status: statusFilter,
            customer_id: customerFilter,
            from_date: fromDate,
            to_date: toDate,
            search: value,
        });
    };

    const clearFilters = () => {
        setStatusFilter('all');
        setCustomerFilter('all');
        setFromDate('');
        setToDate('');
        setSearchTerm('');
        setIsLoading(true);
        router.get(
            preInvoiceRoutes.index().url,
            {},
            {
                preserveState: true,
                replace: true,
                onFinish: () => setIsLoading(false),
            },
        );
    };

    const hasActiveFilters =
        statusFilter !== 'all' ||
        customerFilter !== 'all' ||
        fromDate ||
        toDate ||
        searchTerm;

    const columns: ColumnDef<PreInvoice>[] = [
        {
            accessorKey: 'number',
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                >
                    Número
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            ),
            cell: ({ row }) => (
                <Link
                    href={preInvoiceRoutes.show(row.original.id).url}
                    className="font-mono font-semibold text-primary hover:underline"
                >
                    {row.original.number}
                </Link>
            ),
        },
        {
            accessorKey: 'customer.name',
            header: () => <div>Estado</div>,
            cell: ({ row }) => {
                const customer = row.original.customer;
                const displayName =
                    customer?.fiscal_name || customer?.name || '-';
                return <div className="font-medium">{displayName}</div>;
            },
        },
        {
            accessorKey: 'shipping_order.order_number',
            header: () => <div className="text-center">SO</div>,
            cell: ({ row }) => (
                <span className="font-mono text-xs text-muted-foreground">
                    {row.original.shipping_order?.order_number || '-'}
                </span>
            ),
        },
        {
            accessorKey: 'currency_code',
            header: () => <div className="text-center">Moneda</div>,
            cell: ({ row }) => (
                <span className="font-mono text-xs">
                    {row.original.currency_code}
                </span>
            ),
        },
        {
            accessorKey: 'total_amount',
            header: () => <div className="text-center">Total</div>,
            cell: ({ row }) => (
                <div className="text-right font-mono font-semibold">
                    {formatCurrency(
                        row.original.total_amount,
                        row.original.currency_code,
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: () => <div className="text-center">Estado</div>,
            cell: ({ row }) => (
                <Badge className={statusColors[row.original.status]}>
                    {statusLabels[row.original.status] || row.original.status}
                </Badge>
            ),
        },
        {
            accessorKey: 'issue_date',
            header: () => <div className="text-center">Fecha de emisión </div>,
            cell: ({ row }) => (
                <span className="text-sm">
                    {formatDate(row.original.issue_date)}
                </span>
            ),
        },
        {
            id: 'actions',
            header: () => <div className="px-4 text-right">Acciones</div>,
            cell: ({ row }) => {
                const invoice = row.original;
                return (
                    <div className="flex justify-end">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon">
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={
                                            preInvoiceRoutes.show(invoice.id)
                                                .url
                                        }
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        Ver Detalle
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <a
                                        href={
                                            preInvoiceRoutes.print(invoice.id)
                                                .url
                                        }
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Printer className="mr-2 h-4 w-4" />
                                        Imprimir PDF
                                    </a>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pre-Facturas', href: preInvoiceRoutes.index().url },
            ]}
        >
            <Head title="Pre-Facturas" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Pre-Facturas
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Gestión de facturación preliminar
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild>
                            <Link href={preInvoiceRoutes.create().url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva Pre-Factura
                            </Link>
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">
                                    <Download className="mr-2 h-4 w-4" />
                                    Exportar
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                    <a
                                        href={`${preInvoiceRoutes.export().url}?format=csv${statusFilter !== 'all' ? `&status=${statusFilter}` : ''}`}
                                    >
                                        Exportar CSV
                                    </a>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <a
                                        href={`${preInvoiceRoutes.export().url}?format=json${statusFilter !== 'all' ? `&status=${statusFilter}` : ''}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Exportar JSON
                                    </a>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div className="space-y-4">
                    {/* Filters */}
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="relative w-[280px]">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Buscar por Preinvoice o SO..."
                                value={searchTerm}
                                onChange={(e) =>
                                    handleSearchChange(e.target.value)
                                }
                                className="pl-9"
                            />
                        </div>

                        <Select
                            value={customerFilter}
                            onValueChange={handleCustomerChange}
                        >
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="Cliente" />
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
                                        {customer.code
                                            ? `${customer.code} - ${customer.name}`
                                            : customer.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={statusFilter}
                            onValueChange={handleStatusChange}
                        >
                            <SelectTrigger className="w-[160px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="draft">Borrador</SelectItem>
                                <SelectItem value="issued">Emitida</SelectItem>
                                <SelectItem value="cancelled">
                                    Cancelada
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Input
                            type="date"
                            placeholder="Fecha desde"
                            value={fromDate}
                            onChange={(e) =>
                                handleDateChange('from', e.target.value)
                            }
                            className="w-[160px]"
                        />

                        <Input
                            type="date"
                            placeholder="Fecha hasta"
                            value={toDate}
                            onChange={(e) =>
                                handleDateChange('to', e.target.value)
                            }
                            className="w-[160px]"
                        />

                        {hasActiveFilters && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={clearFilters}
                            >
                                <X className="mr-2 h-4 w-4" />
                                Limpiar filtros
                            </Button>
                        )}

                        {isLoading && (
                            <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                        )}
                    </div>

                    {/* Table - Always visible with headers */}
                    <DataTable
                        columns={columns}
                        data={preInvoices.data}
                        emptyMessage={
                            hasActiveFilters
                                ? 'No se encontraron pre-facturas con los filtros seleccionados.'
                                : 'Todavía no se han generado pre-facturas.'
                        }
                    />

                    {/* Pagination - Reuse logic if DataTable doesn't handle pagination internally (Quote uses external buttons) */}
                    {preInvoices.last_page > 1 && (
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {preInvoices.data.length} de{' '}
                                {preInvoices.total} registros
                            </p>
                            <div className="flex gap-1">
                                {preInvoices.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() =>
                                            link.url &&
                                            router.get(
                                                link.url,
                                                {},
                                                { preserveState: true },
                                            )
                                        }
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
