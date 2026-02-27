import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
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
import { type BreadcrumbItem, type Quote } from '@/types';
import { can as hasPermission } from '@/utils/permissions';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowRight,
    ArrowUpDown,
    Check,
    CheckCircle,
    Eye,
    FileText,
    MoreHorizontal,
    Plane,
    Plus,
    Send,
    Ship,
    Truck,
    X,
    XCircle,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

interface PaginatedQuotes {
    data: Quote[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    quotes: PaginatedQuotes;
    customers: Array<{ id: number; name: string; code: string | null }>;
    statuses: Array<{ value: string; label: string }>;
    filters: {
        status?: string;
        customer_id?: string;
        date_from?: string;
        date_to?: string;
    };
    can: {
        create: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cotizaciones', href: '/quotes' },
];

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    sent: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    approved: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    rejected: 'bg-red-500/10 text-red-400 border-red-500/30',
    expired: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    sent: 'Enviada',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    expired: 'Expirada',
};

const modeIcons: Record<string, React.ReactNode> = {
    AIR: <Plane className="h-4 w-4" />,
    OCEAN: <Ship className="h-4 w-4" />,
    GROUND: <Truck className="h-4 w-4" />,
};

export default function QuotesIndex({
    quotes,
    customers,
    statuses,
    filters,
    can,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };
    const [isLoading, setIsLoading] = useState(false);

    // Filter state
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [customerFilter, setCustomerFilter] = useState(
        filters.customer_id || 'all',
    );
    const [searchTerm, setSearchTerm] = useState('');

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

        router.get('/quotes', params, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => setIsLoading(false),
        });
    }, []);

    const handleStatusChange = (value: string) => {
        setStatusFilter(value);
        applyFilters({ status: value, customer_id: customerFilter });
    };

    const handleCustomerChange = (value: string) => {
        setCustomerFilter(value);
        applyFilters({ status: statusFilter, customer_id: value });
    };

    // Confirmation dialog state
    const [confirmDialog, setConfirmDialog] = useState<{
        open: boolean;
        action: string;
        quote: Quote | null;
        title: string;
        description: string;
    }>({
        open: false,
        action: '',
        quote: null,
        title: '',
        description: '',
    });

    const actionConfigs: Record<
        string,
        { title: string; description: string }
    > = {
        send: {
            title: '¿Enviar cotización?',
            description: 'La cotización será marcada como enviada al cliente.',
        },
        approve: {
            title: '¿Aprobar cotización?',
            description:
                'La cotización será aprobada y podrá convertirse en una orden de envío.',
        },
        reject: {
            title: '¿Rechazar cotización?',
            description: 'La cotización será marcada como rechazada.',
        },
        'convert-to-shipping-order': {
            title: '¿Convertir a orden de envío?',
            description:
                'Se creará una nueva orden de envío basada en esta cotización.',
        },
    };

    // Open confirmation dialog
    const openConfirmDialog = (action: string, quote: Quote) => {
        const config = actionConfigs[action] || {
            title: '¿Confirmar acción?',
            description: '',
        };
        setConfirmDialog({
            open: true,
            action,
            quote,
            title: config.title,
            description: config.description,
        });
    };

    // Execute the action after confirmation
    const executeAction = () => {
        if (!confirmDialog.quote || !confirmDialog.action) return;

        router.post(
            `/quotes/${confirmDialog.quote.id}/${confirmDialog.action}`,
            {},
            {
                preserveScroll: true,
                onFinish: () =>
                    setConfirmDialog({ ...confirmDialog, open: false }),
            },
        );
    };

    // Stats
    const stats = useMemo(
        () => ({
            total: quotes.total,
            draft: quotes.data.filter((q) => q.status === 'draft').length,
            sent: quotes.data.filter((q) => q.status === 'sent').length,
            approved: quotes.data.filter((q) => q.status === 'approved').length,
        }),
        [quotes],
    );

    const columns: ColumnDef<Quote>[] = [
        {
            accessorKey: 'quote_number',
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
                    href={`/quotes/${row.original.id}`}
                    className="font-mono font-semibold text-primary hover:underline"
                >
                    {row.getValue('quote_number')}
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
                const quote = row.original;
                return (
                    <div className="flex items-center gap-2">
                        <span className="font-mono text-sm">
                            {quote.origin_port?.code}
                        </span>
                        <ArrowRight className="h-3 w-3 text-muted-foreground" />
                        <span className="font-mono text-sm">
                            {quote.destination_port?.code}
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
                return (
                    <Badge className={statusColors[status]}>
                        {statusLabels[status] || status}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'total_amount',
            header: () => <div className="text-right">Total</div>,
            cell: ({ row }) => {
                const quote = row.original;
                const symbol = quote.currency?.symbol || '$';
                return (
                    <div className="text-right font-mono font-semibold">
                        {symbol}
                        {Number(quote.total_amount).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                        })}
                    </div>
                );
            },
        },
        {
            accessorKey: 'valid_until',
            header: 'Válida Hasta',
            cell: ({ row }) => {
                const date = row.getValue('valid_until') as string | null;
                if (!date)
                    return <span className="text-muted-foreground">-</span>;
                const formatted = new Date(date).toLocaleDateString('es-DO', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                });
                const isExpired = new Date(date) < new Date();
                return (
                    <span className={isExpired ? 'text-red-400' : ''}>
                        {formatted}
                    </span>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const quote = row.original;
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
                                    <Link href={`/quotes/${quote.id}`}>
                                        <Eye className="mr-2 h-4 w-4" />
                                        Ver Detalle
                                    </Link>
                                </DropdownMenuItem>

                                {quote.status === 'draft' &&
                                    hasPermission('quotes.update') && (
                                        <>
                                            <DropdownMenuItem asChild>
                                                <Link
                                                    href={`/quotes/${quote.id}/edit`}
                                                >
                                                    <FileText className="mr-2 h-4 w-4" />
                                                    Editar
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    openConfirmDialog(
                                                        'send',
                                                        quote,
                                                    )
                                                }
                                            >
                                                <Send className="mr-2 h-4 w-4" />
                                                Enviar
                                            </DropdownMenuItem>
                                        </>
                                    )}

                                {quote.status === 'sent' &&
                                    hasPermission('quotes.approve') && (
                                        <>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    openConfirmDialog(
                                                        'approve',
                                                        quote,
                                                    )
                                                }
                                                className="text-emerald-400"
                                            >
                                                <CheckCircle className="mr-2 h-4 w-4" />
                                                Aprobar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    openConfirmDialog(
                                                        'reject',
                                                        quote,
                                                    )
                                                }
                                                className="text-red-400"
                                            >
                                                <XCircle className="mr-2 h-4 w-4" />
                                                Rechazar
                                            </DropdownMenuItem>
                                        </>
                                    )}

                                {quote.status === 'approved' &&
                                    !quote.has_shipping_order &&
                                    hasPermission('quotes.convert') && (
                                        <>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    openConfirmDialog(
                                                        'convert-to-shipping-order',
                                                        quote,
                                                    )
                                                }
                                                className="text-primary"
                                            >
                                                <Ship className="mr-2 h-4 w-4" />
                                                Convertir a Orden
                                            </DropdownMenuItem>
                                        </>
                                    )}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                );
            },
        },
    ];

    // Filtered data (client-side search only)
    const filteredData = useMemo(() => {
        if (!searchTerm) return quotes.data;
        const term = searchTerm.toLowerCase();
        return quotes.data.filter(
            (q) =>
                q.quote_number.toLowerCase().includes(term) ||
                q.customer?.name.toLowerCase().includes(term) ||
                q.customer?.code?.toLowerCase().includes(term),
        );
    }, [quotes.data, searchTerm]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizaciones" />

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
                                <FileText className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Cotizaciones
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Gestión de cotizaciones para servicios
                                    logísticos
                                </p>
                            </div>
                        </div>

                        {can.create && (
                            <Button asChild className="shadow-md">
                                <Link href="/quotes/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva Cotización
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
                            <FileText className="h-4 w-4 text-violet-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{stats.total}</p>
                    </div>

                    <div className="rounded-lg border border-slate-500/30 bg-gradient-to-br from-card to-slate-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Borradores
                            </p>
                            <FileText className="h-4 w-4 text-slate-400" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{stats.draft}</p>
                    </div>

                    <div className="rounded-lg border border-blue-500/30 bg-gradient-to-br from-card to-blue-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Enviadas
                            </p>
                            <Send className="h-4 w-4 text-blue-400" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{stats.sent}</p>
                    </div>

                    <div className="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Aprobadas
                            </p>
                            <Check className="h-4 w-4 text-emerald-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {stats.approved}
                        </p>
                    </div>
                </div>

                {/* Filters and Table */}
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <Input
                            placeholder="Buscar por número o cliente..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-[280px]"
                        />

                        <Select
                            value={statusFilter}
                            onValueChange={handleStatusChange}
                        >
                            <SelectTrigger className="w-[160px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
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

                        {isLoading && (
                            <span className="text-sm text-muted-foreground">
                                Cargando...
                            </span>
                        )}
                    </div>

                    <DataTable columns={columns} data={filteredData} />

                    {/* Pagination */}
                    {quotes.last_page > 1 && (
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Mostrando {quotes.data.length} de {quotes.total}{' '}
                                cotizaciones
                            </p>
                            <div className="flex gap-1">
                                {quotes.links.map((link, index) => (
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

            {/* Confirmation Dialog */}
            <AlertDialog
                open={confirmDialog.open}
                onOpenChange={(open) =>
                    setConfirmDialog({ ...confirmDialog, open })
                }
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {confirmDialog.title}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {confirmDialog.description}
                            {confirmDialog.quote && (
                                <span className="mt-2 block font-semibold text-foreground">
                                    {confirmDialog.quote.quote_number}
                                </span>
                            )}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={executeAction}>
                            Confirmar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
