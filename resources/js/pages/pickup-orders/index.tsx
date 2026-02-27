import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Eye, Plus, Truck, UserPlus } from 'lucide-react';
import { useState } from 'react';

interface Driver {
    id: number;
    name: string;
}

interface Customer {
    id: number;
    name: string;
}

interface ShippingOrder {
    id: number;
    order_number: string;
}

interface PickupOrder {
    id: number;
    reference: string | null;
    status: string;
    scheduled_date: string | null;
    notes: string | null;
    customer: Customer;
    driver: Driver | null;
    shipping_order: ShippingOrder | null;
}

interface PaginatedOrders {
    data: PickupOrder[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
}

interface Props {
    orders: PaginatedOrders;
    filters: {
        status?: string;
        driver_id?: string;
        scheduled_date?: string;
    };
    drivers: Driver[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Recogidas', href: '/pickup-orders' },
];

const STATUS_OPTIONS = [
    { value: 'all', label: 'Todos los estados' },
    { value: 'pending', label: 'Pendiente' },
    { value: 'assigned', label: 'Asignado' },
    { value: 'in_progress', label: 'En Progreso' },
    { value: 'completed', label: 'Completado' },
    { value: 'cancelled', label: 'Cancelado' },
];

const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-800',
    assigned: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'Pendiente',
    assigned: 'Asignado',
    in_progress: 'En Progreso',
    completed: 'Completado',
    cancelled: 'Cancelado',
};

const formatDate = (dateString: string | null): string => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-DO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

export default function PickupOrdersIndex({ orders, filters, drivers }: Props) {
    const [assignDialogOpen, setAssignDialogOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState<PickupOrder | null>(
        null,
    );
    const [selectedDriver, setSelectedDriver] = useState<string>('');
    const [isAssigning, setIsAssigning] = useState(false);

    // Filter handlers
    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/pickup-orders',
            {
                ...filters,
                [key]: value === 'all' ? undefined : value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    // Open assign dialog
    const openAssignDialog = (order: PickupOrder) => {
        setSelectedOrder(order);
        setSelectedDriver('');
        setAssignDialogOpen(true);
    };

    // Handle driver assignment
    const handleAssignDriver = () => {
        if (!selectedOrder || !selectedDriver) return;

        setIsAssigning(true);
        router.post(
            `/pickup-orders/${selectedOrder.id}/assign-driver`,
            { driver_id: selectedDriver },
            {
                onSuccess: () => {
                    setAssignDialogOpen(false);
                    setSelectedOrder(null);
                    setSelectedDriver('');
                },
                onFinish: () => setIsAssigning(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recogidas" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Truck className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">Recogidas</h1>
                            <p className="text-sm text-muted-foreground">
                                {orders.total} órdenes de recogida
                            </p>
                        </div>
                    </div>
                    {can('pickup_orders.create') && (
                        <Link href="/pickup-orders/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva Recogida
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="w-48">
                                <Label className="text-xs">Estado</Label>
                                <Select
                                    value={filters.status || 'all'}
                                    onValueChange={(v) =>
                                        handleFilterChange('status', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {STATUS_OPTIONS.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-48">
                                <Label className="text-xs">Conductor</Label>
                                <Select
                                    value={filters.driver_id || 'all'}
                                    onValueChange={(v) =>
                                        handleFilterChange('driver_id', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos los conductores
                                        </SelectItem>
                                        {drivers.map((driver) => (
                                            <SelectItem
                                                key={driver.id}
                                                value={driver.id.toString()}
                                            >
                                                {driver.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-48">
                                <Label className="text-xs">
                                    Fecha Programada
                                </Label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={filters.scheduled_date || ''}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            'scheduled_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <Button
                                variant={
                                    filters.scheduled_date ===
                                    new Date().toISOString().split('T')[0]
                                        ? 'default'
                                        : 'outline'
                                }
                                size="sm"
                                onClick={() =>
                                    handleFilterChange(
                                        'scheduled_date',
                                        new Date().toISOString().split('T')[0],
                                    )
                                }
                            >
                                Hoy
                            </Button>
                            {(filters.status ||
                                filters.driver_id ||
                                filters.scheduled_date) && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        router.get(
                                            '/pickup-orders',
                                            {},
                                            { preserveState: true },
                                        )
                                    }
                                >
                                    Limpiar filtros
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-20">ID</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>Orden Envío</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Conductor</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {orders.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No hay órdenes de recogida
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    orders.data.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="font-medium">
                                                #{order.id}
                                            </TableCell>
                                            <TableCell>
                                                {order.customer?.name || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {order.shipping_order ? (
                                                    <Link
                                                        href={`/shipping-orders/${order.shipping_order.id}`}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {
                                                            order.shipping_order
                                                                .order_number
                                                        }
                                                    </Link>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        -
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    className={
                                                        STATUS_COLORS[
                                                            order.status
                                                        ] || ''
                                                    }
                                                >
                                                    {STATUS_LABELS[
                                                        order.status
                                                    ] || order.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {order.driver?.name || (
                                                    <span className="text-muted-foreground">
                                                        Sin asignar
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    order.scheduled_date,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Link
                                                        href={`/pickup-orders/${order.id}`}
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    {order.status ===
                                                        'pending' &&
                                                        can(
                                                            'pickup_orders.assign_driver',
                                                        ) && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    openAssignDialog(
                                                                        order,
                                                                    )
                                                                }
                                                            >
                                                                <UserPlus className="mr-1 h-4 w-4" />
                                                                Asignar
                                                            </Button>
                                                        )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {orders.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {orders.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Assign Driver Dialog */}
            <Dialog open={assignDialogOpen} onOpenChange={setAssignDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Asignar Conductor</DialogTitle>
                        <DialogDescription>
                            Seleccione un conductor para la recogida #
                            {selectedOrder?.id}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-4">
                        <Label>Conductor</Label>
                        <Select
                            value={selectedDriver}
                            onValueChange={setSelectedDriver}
                        >
                            <SelectTrigger className="mt-2">
                                <SelectValue placeholder="Seleccionar conductor..." />
                            </SelectTrigger>
                            <SelectContent>
                                {drivers.map((driver) => (
                                    <SelectItem
                                        key={driver.id}
                                        value={driver.id.toString()}
                                    >
                                        {driver.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setAssignDialogOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleAssignDriver}
                            disabled={!selectedDriver || isAssigning}
                        >
                            {isAssigning ? 'Asignando...' : 'Asignar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
