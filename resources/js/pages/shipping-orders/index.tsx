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
import shipRoutes from '@/routes/shipping-orders';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowRight,
    ArrowUpDown,
    Calendar,
    Check,
    Eye,
    Filter,
    MoreHorizontal,
    Plane,
    Plus,
    Search,
    Ship,
    Truck,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

interface ShippingOrder {
    id: number;
    order_number: string;
    customer?: { id: number; name: string; code: string | null };
    origin_port?: { id: number; code: string; name: string };
    destination_port?: { id: number; code: string; name: string };
    transport_mode?: { id: number; code: string; name: string };
    service_type?: { id: number; code: string; name: string };
    currency?: { id: number; code: string; symbol: string };
    quote?: { id: number; quote_number: string };
    status: string;
    total_amount: number;
    planned_departure_at: string | null;
    planned_arrival_at: string | null;
    created_at: string;
}

interface PaginatedOrders {
    data: ShippingOrder[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Port {
    id: number;
    code: string;
    name: string;
    country?: string;
}

interface Props {
    orders: PaginatedOrders;
    customers: Array<{ id: number; name: string; code: string | null }>;
    ports: Port[];
    statuses: Array<{ value: string; label: string; color: string }>;
    filters: {
        status?: string;
        customer_id?: string;
        origin_port_id?: string;
        destination_port_id?: string;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
    can: {
        create: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Órdenes de Envío', href: shipRoutes.index.url() },
];

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    booked: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    in_transit: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    arrived: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
    delivered: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    closed: 'bg-gray-500/10 text-gray-400 border-gray-500/30',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/30',
};

const modeIcons: Record<string, React.ReactNode> = {
    AIR: <Plane className="h-4 w-4" />,
    OCEAN: <Ship className="h-4 w-4" />,
    GROUND: <Truck className="h-4 w-4" />,
};

export default function ShippingOrdersIndex({
    orders,
    customers,
    ports,
    statuses,
    filters,
    can,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [isLoading, setIsLoading] = useState(false);

    // Filter state - initialize from server-provided filters
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [customerFilter, setCustomerFilter] = useState(
        filters.customer_id || 'all',
    );
    const [originPortFilter, setOriginPortFilter] = useState(
        filters.origin_port_id || 'all',
    );
    const [destinationPortFilter, setDestinationPortFilter] = useState(
        filters.destination_port_id || 'all',
    );
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

    // Debounce timer for search
    const searchTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Check if any filters are active
    const hasActiveFilters = useMemo(() => {
        return (
            statusFilter !== 'all' ||
            customerFilter !== 'all' ||
            originPortFilter !== 'all' ||
            destinationPortFilter !== 'all' ||
            dateFrom !== '' ||
            dateTo !== '' ||
            searchTerm !== ''
        );
    }, [
        statusFilter,
        customerFilter,
        originPortFilter,
        destinationPortFilter,
        dateFrom,
        dateTo,
        searchTerm,
    ]);

    // Build params object from current filter state
    const buildParams = useCallback(
        (overrides: Record<string, string> = {}) => {
            const params: Record<string, string> = {};

            const currentStatus =
                overrides.status !== undefined
                    ? overrides.status
                    : statusFilter;
            const currentCustomer =
                overrides.customer_id !== undefined
                    ? overrides.customer_id
                    : customerFilter;
            const currentOrigin =
                overrides.origin_port_id !== undefined
                    ? overrides.origin_port_id
                    : originPortFilter;
            const currentDestination =
                overrides.destination_port_id !== undefined
                    ? overrides.destination_port_id
                    : destinationPortFilter;
            const currentDateFrom =
                overrides.date_from !== undefined
                    ? overrides.date_from
                    : dateFrom;
            const currentDateTo =
                overrides.date_to !== undefined ? overrides.date_to : dateTo;
            const currentSearch =
                overrides.search !== undefined ? overrides.search : searchTerm;

            if (currentStatus && currentStatus !== 'all') {
                params.status = currentStatus;
            }
            if (currentCustomer && currentCustomer !== 'all') {
                params.customer_id = currentCustomer;
            }
            if (currentOrigin && currentOrigin !== 'all') {
                params.origin_port_id = currentOrigin;
            }
            if (currentDestination && currentDestination !== 'all') {
                params.destination_port_id = currentDestination;
            }
            if (currentDateFrom) {
                params.date_from = currentDateFrom;
            }
            if (currentDateTo) {
                params.date_to = currentDateTo;
            }
            if (currentSearch) {
                params.search = currentSearch;
            }

            return params;
        },
        [
            statusFilter,
            customerFilter,
            originPortFilter,
            destinationPortFilter,
            dateFrom,
            dateTo,
            searchTerm,
        ],
    );

    // Apply filters via Inertia
    const applyFilters = useCallback(
        (overrides: Record<string, string> = {}) => {
            setIsLoading(true);
            const params = buildParams(overrides);

            router.get(shipRoutes.index().url, params, {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsLoading(false),
            });
        },
        [buildParams],
    );

    // Handle filter changes
    const handleStatusChange = (value: string) => {
        setStatusFilter(value);
        applyFilters({ status: value });
    };

    const handleCustomerChange = (value: string) => {
        setCustomerFilter(value);
        applyFilters({ customer_id: value });
    };

    const handleOriginPortChange = (value: string) => {
        setOriginPortFilter(value);
        applyFilters({ origin_port_id: value });
    };

    const handleDestinationPortChange = (value: string) => {
        setDestinationPortFilter(value);
        applyFilters({ destination_port_id: value });
    };

    const handleDateFromChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setDateFrom(value);
        applyFilters({ date_from: value });
    };

    const handleDateToChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setDateTo(value);
        applyFilters({ date_to: value });
    };

    // Debounced search
    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setSearchTerm(value);

        // Clear previous timeout
        if (searchTimeoutRef.current) {
            clearTimeout(searchTimeoutRef.current);
        }

        // Set new timeout (debounce 400ms)
        searchTimeoutRef.current = setTimeout(() => {
            applyFilters({ search: value });
        }, 400);
    };

    // Cleanup timeout on unmount
    useEffect(() => {
        return () => {
            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }
        };
    }, []);

    // Clear all filters
    const clearFilters = () => {
        setStatusFilter('all');
        setCustomerFilter('all');
        setOriginPortFilter('all');
        setDestinationPortFilter('all');
        setDateFrom('');
        setDateTo('');
        setSearchTerm('');

        setIsLoading(true);
        router.get(
            shipRoutes.index().url,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsLoading(false),
            },
        );
    };

    // Stats
    const stats = useMemo(
        () => ({
            total: orders.total,
            active: orders.data.filter((o) =>
                ['booked', 'in_transit', 'arrived'].includes(o.status),
            ).length,
            delivered: orders.data.filter((o) => o.status === 'delivered')
                .length,
            draft: orders.data.filter((o) => o.status === 'draft').length,
        }),
        [orders],
    );

    const formatDate = (date: string | null) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('es-DO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    const columns: ColumnDef<ShippingOrder>[] = [
        {
            accessorKey: 'order_number',
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
                    href={
                        shipRoutes.show({ shippingOrder: row.original.id }).url
                    }
                    className="font-mono font-semibold text-primary hover:underline"
                >
                    {row.getValue('order_number')}
                </Link>
            ),
        },
        {
            accessorKey: 'customer.name',
            header: 'Cliente',
            cell: ({ row }) => {
                const customer = row.original.customer;
                return (
                    <div className="flex flex-col">
                        <span className="font-medium">{customer?.name}</span>
                        {customer?.code && (
                            <span className="text-xs text-muted-foreground">
                                {customer.code}
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            id: 'lane',
            header: 'Ruta',
            cell: ({ row }) => {
                const order = row.original;
                return (
                    <div className="flex items-center gap-2">
                        <span className="font-mono text-sm">
                            {order.origin_port?.code}
                        </span>
                        <ArrowRight className="h-3 w-3 text-muted-foreground" />
                        <span className="font-mono text-sm">
                            {order.destination_port?.code}
                        </span>
                    </div>
                );
            },
        },
        {
            accessorKey: 'transport_mode.code',
            header: 'Modo',
            cell: ({ row }) => {
                const mode = row.original.transport_mode;
                return (
                    <div
                        className="flex items-center gap-1.5"
                        title={mode?.name}
                    >
                        {modeIcons[mode?.code || ''] || (
                            <Plane className="h-4 w-4" />
                        )}
                        <span className="text-sm">{mode?.code}</span>
                    </div>
                );
            },
        },
        {
            accessorKey: 'status',
            header: 'Estado',
            cell: ({ row }) => {
                const status = row.getValue('status') as string;
                const statusInfo = statuses.find((s) => s.value === status);
                return (
                    <Badge className={statusColors[status]}>
                        {statusInfo?.label || status}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'total_amount',
            header: () => <div className="text-right">Total</div>,
            cell: ({ row }) => {
                const order = row.original;
                const symbol = order.currency?.symbol || '$';
                return (
                    <div className="text-right font-mono font-semibold">
                        {symbol}
                        {Number(order.total_amount).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                        })}
                    </div>
                );
            },
        },
        {
            accessorKey: 'planned_departure_at',
            header: 'Salida',
            cell: ({ row }) => formatDate(row.original.planned_departure_at),
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const order = row.original;
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
                                            shipRoutes.show({
                                                shippingOrder: order.id,
                                            }).url
                                        }
                                    >
                                        <Eye className="mr-2 h-4 w-4" />
                                        Ver Detalle
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Órdenes de Envío" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-400">
                        <Check className="h-5 w-5" />
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-red-400">
                        <X className="h-5 w-5" />
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <Ship className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Órdenes de Envío
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Gestión de órdenes de envío y seguimiento
                                </p>
                            </div>
                        </div>

                        {can.create && (
                            <Button asChild className="shadow-md">
                                <Link href={shipRoutes.create.url()}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva Orden
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-lg border border-violet-500/30 bg-gradient-to-br from-card to-violet-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Total
                            </p>
                            <Ship className="h-4 w-4 text-violet-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{stats.total}</p>
                    </div>

                    <div className="rounded-lg border border-amber-500/30 bg-gradient-to-br from-card to-amber-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                En Tránsito
                            </p>
                            <Truck className="h-4 w-4 text-amber-400" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {stats.active}
                        </p>
                    </div>

                    <div className="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Entregadas
                            </p>
                            <Check className="h-4 w-4 text-emerald-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {stats.delivered}
                        </p>
                    </div>

                    <div className="rounded-lg border border-slate-500/30 bg-gradient-to-br from-card to-slate-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Borradores
                            </p>
                            <Ship className="h-4 w-4 text-slate-400" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{stats.draft}</p>
                    </div>
                </div>

                {/* Filters Panel */}
                <div className="space-y-4 rounded-lg border bg-card/50 p-4">
                    {/* Row 1: Search and core filters */}
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="relative min-w-[280px] flex-1">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Buscar por SO #, Quote # o cliente..."
                                value={searchTerm}
                                onChange={handleSearchChange}
                                className="pl-9"
                            />
                        </div>

                        <Select
                            value={statusFilter}
                            onValueChange={handleStatusChange}
                        >
                            <SelectTrigger className="w-[160px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todos los estados
                                </SelectItem>
                                {statuses.map((s) => (
                                    <SelectItem key={s.value} value={s.value}>
                                        {s.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

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
                                {customers.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Row 2: Ports and dates */}
                    <div className="flex flex-wrap items-center gap-3">
                        <Select
                            value={originPortFilter}
                            onValueChange={handleOriginPortChange}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Puerto origen" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todos los orígenes
                                </SelectItem>
                                {ports.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>
                                        {p.code} - {p.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={destinationPortFilter}
                            onValueChange={handleDestinationPortChange}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Puerto destino" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todos los destinos
                                </SelectItem>
                                {ports.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>
                                        {p.code} - {p.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <div className="flex items-center gap-2">
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                            <Input
                                type="date"
                                value={dateFrom}
                                onChange={handleDateFromChange}
                                className="w-[150px]"
                                title="Fecha desde"
                            />
                            <span className="text-muted-foreground">-</span>
                            <Input
                                type="date"
                                value={dateTo}
                                onChange={handleDateToChange}
                                className="w-[150px]"
                                title="Fecha hasta"
                            />
                        </div>

                        {hasActiveFilters && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={clearFilters}
                                className="ml-auto"
                            >
                                <X className="mr-1 h-4 w-4" />
                                Limpiar filtros
                            </Button>
                        )}

                        {isLoading && (
                            <span className="text-sm text-muted-foreground">
                                Cargando...
                            </span>
                        )}
                    </div>

                    {/* Active filters indicator */}
                    {hasActiveFilters && (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Filter className="h-4 w-4" />
                            <span>
                                Filtros activos:{' '}
                                {[
                                    statusFilter !== 'all' && 'Estado',
                                    customerFilter !== 'all' && 'Cliente',
                                    originPortFilter !== 'all' && 'Origen',
                                    destinationPortFilter !== 'all' &&
                                        'Destino',
                                    dateFrom && 'Desde',
                                    dateTo && 'Hasta',
                                    searchTerm && 'Búsqueda',
                                ]
                                    .filter(Boolean)
                                    .join(', ')}
                            </span>
                        </div>
                    )}
                </div>

                {/* Table */}
                <div className="space-y-4">
                    <DataTable columns={columns} data={orders.data} />

                    {/* Pagination */}
                    {orders.last_page > 1 && (
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {orders.data.length} de {orders.total}{' '}
                                órdenes
                            </p>
                            <div className="flex gap-1">
                                {orders.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => {
                                            if (link.url) {
                                                router.get(
                                                    link.url,
                                                    {},
                                                    { preserveState: true },
                                                );
                                            }
                                        }}
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
