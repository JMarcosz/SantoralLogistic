import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
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
import { Customer, Payment, PaymentMethod } from '@/pages/payments/utils/types/payment';
import { Head, Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    CircleDollarSign,
    Eye,
    Loader2,
    Plus,
    Search,
    X,
} from 'lucide-react';
import { useCallback, useState } from 'react';

interface PaginatedPayments {
    data: Payment[];
    current_page: number;
    last_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    payments: PaginatedPayments;
    customers: Customer[];
    paymentMethods: PaymentMethod[];
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
    pending: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    approved: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
    posted: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    voided: 'bg-red-500/10 text-red-400 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    pending: 'Pendiente',
    approved: 'Aprobado',
    posted: 'Contabilizado',
    voided: 'Anulado',
};

export default function PaymentsIndex({
    payments,
    customers,
    paymentMethods,
    filters,
}: Props) {
    const [isLoading, setIsLoading] = useState(false);

    // Filter customers to only show those with payments
    const customersWithPayments = customers.filter((customer) =>
        payments.data.some((payment) => payment.customer?.id === customer.id),
    );

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

        if (newFilters.type && newFilters.type !== 'all') {
            params.type = newFilters.type;
        }
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

        router.get('/payments', params, {
            preserveState: true,
            replace: true,
            onFinish: () => setIsLoading(false),
        });
    }, []);

    const handleFilterChange = (field: string, value: string) => {
        const filterState = {
            status: statusFilter,
            customer_id: customerFilter,
            from_date: fromDate,
            to_date: toDate,
            search: searchTerm,
        };

        filterState[field as keyof typeof filterState] = value;

        // Update local state
        switch (field) {
            case 'status':
                setStatusFilter(value);
                break;
            case 'customer_id':
                setCustomerFilter(value);
                break;
            case 'from_date':
                setFromDate(value);
                break;
            case 'to_date':
                setToDate(value);
                break;
            case 'search':
                setSearchTerm(value);
                break;
        }

        applyFilters(filterState);
    };

    const clearFilters = () => {
        setStatusFilter('all');
        setCustomerFilter('all');
        setFromDate('');
        setToDate('');
        setSearchTerm('');
        setIsLoading(true);
        router.get(
            '/payments',
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

    const columns: ColumnDef<Payment>[] = [
        {
            accessorKey: 'payment_number',
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
                    href={`/payments/${row.original.id}`}
                    className="font-mono font-semibold text-primary hover:underline"
                >
                    {row.original.payment_number || `#${row.original.id}`}
                </Link>
            ),
        },
        {
            accessorKey: 'customer.name',
            header: () => <div>Cliente</div>,
            cell: ({ row }) => {
                const customer = row.original.customer;
                const displayName =
                    customer?.fiscal_name || customer?.name || '-';
                return <div className="font-medium">{displayName}</div>;
            },
        },
        {
            accessorKey: 'payment_date',
            header: () => <div className="text-center">Fecha</div>,
            cell: ({ row }) => (
                <span className="text-sm">
                    {formatDate(row.original.payment_date)}
                </span>
            ),
        },
        {
            accessorKey: 'payment_method.name',
            header: () => <div>Método</div>,
            cell: ({ row }) => (
                <span className="text-sm">
                    {row.original.payment_method?.name || '-'}
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
            accessorKey: 'amount',
            header: () => <div className="text-right">Monto</div>,
            cell: ({ row }) => (
                <div className="text-right font-mono font-semibold">
                    {formatCurrency(
                        row.original.amount,
                        row.original.currency_code,
                    )}
                </div>
            ),
        },
        {
            accessorKey: 'status',
            header: () => <div className="text-center">Estado</div>,
            cell: ({ row }) => (
                <Badge
                    className={
                        statusColors[row.original.status] || statusColors.draft
                    }
                >
                    {statusLabels[row.original.status] || row.original.status}
                </Badge>
            ),
        },
        {
            accessorKey: 'reference',
            header: () => <div>Referencia</div>,
            cell: ({ row }) => (
                <span className="font-mono text-xs text-muted-foreground">
                    {row.original.reference || '-'}
                </span>
            ),
        },
        {
            id: 'actions',
            header: () => <div className="px-4 text-right">Acciones</div>,
            cell: ({ row }) => {
                const payment = row.original;
                return (
                    <div className="flex justify-end">
                        <Link
                            className="justify flex content-between items-center rounded-lg border p-3"
                            href={`/payments/${payment.id}`}
                        >
                            <Eye className="mr-2 h-4 w-4" />
                            Ver detalles
                        </Link>
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={[{ title: 'Pagos', href: '/payments' }]}>
            <Head title="Pagos" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10">
                                <CircleDollarSign className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold">
                                    Gestión de pagos
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {payments.total} pagos
                                </p>
                            </div>
                        </div>
                    </div>
                    <Button onClick={() => router.visit('/payments/create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Registrar Cobro
                    </Button>
                </div>

                {/* Filters */}
                <div className="grid gap-4 md:grid-cols-6">
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Cliente</label>
                        <Select
                            value={customerFilter}
                            onValueChange={(value) =>
                                handleFilterChange('customer_id', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {customersWithPayments.map((customer) => (
                                    <SelectItem
                                        key={customer.id}
                                        value={customer.id.toString()}
                                    >
                                        {customer.fiscal_name || customer.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <label className="text-sm font-medium">Estado</label>
                        <Select
                            value={statusFilter}
                            onValueChange={(value) =>
                                handleFilterChange('status', value)
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="draft">Borrador</SelectItem>
                                <SelectItem value="pending">
                                    Pendiente
                                </SelectItem>
                                <SelectItem value="approved">
                                    Aprobado
                                </SelectItem>
                                <SelectItem value="posted">
                                    Contabilizado
                                </SelectItem>
                                <SelectItem value="voided">Anulado</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Desde</label>
                        <Input
                            type="date"
                            value={fromDate}
                            onChange={(e) =>
                                handleFilterChange('from_date', e.target.value)
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Hasta</label>
                        <Input
                            type="date"
                            value={toDate}
                            onChange={(e) =>
                                handleFilterChange('to_date', e.target.value)
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <label className="text-sm font-medium">Buscar</label>
                        <div className="relative">
                            <Search className="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Número, referencia..."
                                value={searchTerm}
                                onChange={(e) =>
                                    handleFilterChange('search', e.target.value)
                                }
                                className="pl-8"
                            />
                        </div>
                    </div>
                </div>

                {hasActiveFilters && (
                    <div className="flex items-center justify-between rounded-lg border bg-muted/50 px-4 py-2">
                        <p className="text-sm text-muted-foreground">
                            Filtros activos
                        </p>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={clearFilters}
                        >
                            <X className="mr-2 h-4 w-4" />
                            Limpiar filtros
                        </Button>
                    </div>
                )}

                {/* Data Table */}
                {isLoading ? (
                    <div className="flex items-center justify-center py-12">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    </div>
                ) : (
                    <DataTable columns={columns} data={payments.data} />
                )}

                {/* Pagination Info */}
                <div className="text-sm text-muted-foreground">
                    Mostrando {payments.data.length} de {payments.total} pagos
                </div>
            </div>
        </AppLayout>
    );
}
