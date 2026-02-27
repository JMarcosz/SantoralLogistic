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
import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Box, Filter, Package, Truck, XCircle } from 'lucide-react';

interface WarehouseOrder {
    id: number;
    warehouse: { id: number; name: string; code: string };
    shipping_order: {
        id: number;
        order_number: string;
        customer: { id: number; name: string } | null;
    };
    status: string;
    status_label: string;
    status_color: string;
    reference: string | null;
    lines_count: number;
    created_at: string;
}

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface PaginatedData {
    data: WarehouseOrder[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface PageProps {
    orders: PaginatedData;
    filters: { status?: string; warehouse_id?: string };
    warehouses: Warehouse[];
    [key: string]: unknown;
}

const statusColors: Record<string, string> = {
    slate: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
    amber: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
    blue: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
    emerald: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    red: 'bg-red-500/20 text-red-400 border-red-500/30',
};

const statusIcons: Record<string, React.ReactNode> = {
    pending: <Box className="h-4 w-4" />,
    picking: <Package className="h-4 w-4" />,
    packed: <Package className="h-4 w-4" />,
    dispatched: <Truck className="h-4 w-4" />,
    cancelled: <XCircle className="h-4 w-4" />,
};

export default function WarehouseOrdersIndex({
    orders,
    filters,
    warehouses,
}: PageProps) {
    const handleFilter = (key: string, value: string) => {
        router.get(
            '/warehouse-orders',
            { ...filters, [key]: value === 'all' ? '' : value },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        router.get('/warehouse-orders', {}, { preserveState: true });
    };

    const hasFilters = filters.status || filters.warehouse_id;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Almacén', href: '/warehouse/dashboard' },
                { title: 'Órdenes de Almacén', href: '/warehouse-orders' },
            ]}
        >
            <Head title="Órdenes de Almacén" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            <Package className="h-6 w-6" />
                            Órdenes de Almacén
                        </h1>
                        <p className="text-muted-foreground">
                            Gestión de picking, empaque y despacho
                        </p>
                    </div>
                    <Badge variant="outline" className="px-4 py-2 text-lg">
                        {orders.total} órdenes
                    </Badge>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Filter className="h-4 w-4" />
                            Filtros
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-4">
                            <div className="w-48">
                                <Select
                                    value={filters.status || 'all'}
                                    onValueChange={(v) =>
                                        handleFilter('status', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los estados
                                        </SelectItem>
                                        <SelectItem value="pending">
                                            Pendiente
                                        </SelectItem>
                                        <SelectItem value="picking">
                                            En Picking
                                        </SelectItem>
                                        <SelectItem value="packed">
                                            Empacado
                                        </SelectItem>
                                        <SelectItem value="dispatched">
                                            Despachado
                                        </SelectItem>
                                        <SelectItem value="cancelled">
                                            Cancelado
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-56">
                                <Select
                                    value={filters.warehouse_id || 'all'}
                                    onValueChange={(v) =>
                                        handleFilter('warehouse_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Almacén" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los almacenes
                                        </SelectItem>
                                        {warehouses.map((w) => (
                                            <SelectItem
                                                key={w.id}
                                                value={String(w.id)}
                                            >
                                                {w.code} - {w.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {hasFilters && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearFilters}
                                >
                                    Limpiar filtros
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Orders Table */}
                <Card>
                    <CardContent className="p-0">
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-20">
                                            ID
                                        </TableHead>
                                        <TableHead>Orden de Envío</TableHead>
                                        <TableHead>Cliente</TableHead>
                                        <TableHead>Almacén</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead className="text-center">
                                            Líneas
                                        </TableHead>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead className="w-20"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={8}
                                                className="py-12 text-center text-muted-foreground"
                                            >
                                                No hay órdenes de almacén
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        orders.data.map((order) => (
                                            <TableRow
                                                key={order.id}
                                                className="cursor-pointer hover:bg-muted/50"
                                                onClick={() =>
                                                    router.visit(
                                                        `/warehouse-orders/${order.id}`,
                                                    )
                                                }
                                            >
                                                <TableCell className="font-mono">
                                                    #{order.id}
                                                </TableCell>
                                                <TableCell className="font-medium">
                                                    {
                                                        order.shipping_order
                                                            .order_number
                                                    }
                                                </TableCell>
                                                <TableCell>
                                                    {order.shipping_order
                                                        .customer?.name || '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-muted-foreground">
                                                        {order.warehouse.code}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="outline"
                                                        className={`${statusColors[order.status_color]} flex w-fit items-center gap-1`}
                                                    >
                                                        {
                                                            statusIcons[
                                                                order.status
                                                            ]
                                                        }
                                                        {order.status_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {order.lines_count}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {order.created_at}
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/warehouse-orders/${order.id}`}
                                                        >
                                                            <ArrowRight className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {orders.last_page > 1 && (
                            <div className="flex items-center justify-between border-t px-4 py-3">
                                <p className="text-sm text-muted-foreground">
                                    Mostrando {orders.data.length} de{' '}
                                    {orders.total} órdenes
                                </p>
                                <div className="flex gap-1">
                                    {orders.links.map((link, i) => (
                                        <Button
                                            key={i}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() =>
                                                link.url &&
                                                router.visit(link.url)
                                            }
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
