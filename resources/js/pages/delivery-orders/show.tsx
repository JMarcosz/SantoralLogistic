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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Camera,
    CheckCircle,
    MapPin,
    Package,
    Play,
    UserPlus,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import PodDialog from '../pickup-orders/components/PodDialog';
import PodDisplay from '../pickup-orders/components/PodDisplay';

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

interface Stop {
    id: number;
    sequence: number;
    name: string;
    address: string;
    city: string | null;
    country: string | null;
    window_start: string | null;
    window_end: string | null;
    contact_name: string | null;
    contact_phone: string | null;
}

interface Pod {
    id: number;
    happened_at: string;
    latitude: string | null;
    longitude: string | null;
    image_path: string | null;
    notes: string | null;
    created_by: {
        id: number;
        name: string;
    } | null;
    created_at: string;
}

interface DeliveryOrder {
    id: number;
    reference: string | null;
    status: string;
    scheduled_date: string | null;
    notes: string | null;
    customer: Customer;
    driver: Driver | null;
    shipping_order: ShippingOrder | null;
    stops: Stop[];
    pod: Pod | null;
}

interface Props {
    order: DeliveryOrder;
    allowedTransitions: string[];
    drivers: Driver[];
    canRegisterPod: boolean;
}

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

export default function DeliveryOrderShow({
    order,
    allowedTransitions,
    drivers,
    canRegisterPod,
}: Props) {
    const [assignDialogOpen, setAssignDialogOpen] = useState(false);
    const [podDialogOpen, setPodDialogOpen] = useState(false);
    const [selectedDriver, setSelectedDriver] = useState<string>('');
    const [isProcessing, setIsProcessing] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inicio', href: '/dashboard' },
        { title: 'Entregas', href: '/delivery-orders' },
        { title: `#${order.id}`, href: `/delivery-orders/${order.id}` },
    ];

    const handleAction = (action: string) => {
        setIsProcessing(true);
        router.post(
            `/delivery-orders/${order.id}/${action}`,
            {},
            { onFinish: () => setIsProcessing(false) },
        );
    };

    const handleAssignDriver = () => {
        if (!selectedDriver) return;
        setIsProcessing(true);
        router.post(
            `/delivery-orders/${order.id}/assign-driver`,
            { driver_id: selectedDriver },
            {
                onSuccess: () => {
                    setAssignDialogOpen(false);
                    setSelectedDriver('');
                },
                onFinish: () => setIsProcessing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Entrega #${order.id}`} />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/delivery-orders">
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10">
                                <Package className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold">
                                    Entrega #{order.id}
                                </h1>
                                <Badge
                                    className={
                                        STATUS_COLORS[order.status] || ''
                                    }
                                >
                                    {STATUS_LABELS[order.status] ||
                                        order.status}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-2">
                        {allowedTransitions.includes('assign') && (
                            <Button
                                variant="outline"
                                onClick={() => setAssignDialogOpen(true)}
                                disabled={isProcessing}
                            >
                                <UserPlus className="mr-2 h-4 w-4" />
                                Asignar Conductor
                            </Button>
                        )}
                        {allowedTransitions.includes('start') && (
                            <Button
                                onClick={() => handleAction('start')}
                                disabled={isProcessing}
                            >
                                <Play className="mr-2 h-4 w-4" />
                                Iniciar
                            </Button>
                        )}
                        {canRegisterPod && (
                            <Button
                                onClick={() => setPodDialogOpen(true)}
                                disabled={isProcessing}
                                className="bg-green-600 hover:bg-green-700"
                            >
                                <Camera className="mr-2 h-4 w-4" />
                                Registrar POD
                            </Button>
                        )}
                        {allowedTransitions.includes('complete') &&
                            !canRegisterPod && (
                                <Button
                                    onClick={() => handleAction('complete')}
                                    disabled={isProcessing}
                                    className="bg-green-600 hover:bg-green-700"
                                >
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Completar
                                </Button>
                            )}
                        {allowedTransitions.includes('cancel') && (
                            <Button
                                variant="destructive"
                                onClick={() => handleAction('cancel')}
                                disabled={isProcessing}
                            >
                                <XCircle className="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Order Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles de la Orden</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label className="text-muted-foreground">
                                        Cliente
                                    </Label>
                                    <p className="font-medium">
                                        {order.customer?.name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-muted-foreground">
                                        Conductor
                                    </Label>
                                    <p className="font-medium">
                                        {order.driver?.name || 'Sin asignar'}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-muted-foreground">
                                        Fecha Programada
                                    </Label>
                                    <p className="font-medium">
                                        {formatDate(order.scheduled_date) ||
                                            '-'}
                                    </p>
                                </div>
                                <div>
                                    <Label className="text-muted-foreground">
                                        Referencia
                                    </Label>
                                    <p className="font-medium">
                                        {order.reference || '-'}
                                    </p>
                                </div>
                            </div>
                            {order.shipping_order && (
                                <div>
                                    <Label className="text-muted-foreground">
                                        Orden de Envío
                                    </Label>
                                    <Link
                                        href={`/shipping-orders/${order.shipping_order.id}`}
                                        className="block font-medium text-primary hover:underline"
                                    >
                                        {order.shipping_order.order_number}
                                    </Link>
                                </div>
                            )}
                            {order.notes && (
                                <div>
                                    <Label className="text-muted-foreground">
                                        Notas
                                    </Label>
                                    <p className="text-sm whitespace-pre-wrap">
                                        {order.notes}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Stops */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="h-4 w-4" />
                                Paradas ({order.stops?.length || 0})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {order.stops && order.stops.length > 0 ? (
                                <div className="space-y-4">
                                    {order.stops.map((stop) => (
                                        <div
                                            key={stop.id}
                                            className="flex gap-3 border-b pb-3 last:border-0"
                                        >
                                            <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-600 text-xs font-bold text-white">
                                                {stop.sequence}
                                            </div>
                                            <div className="flex-1">
                                                <p className="font-medium">
                                                    {stop.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {stop.address}
                                                    {stop.city &&
                                                        `, ${stop.city}`}
                                                </p>
                                                {stop.contact_name && (
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {stop.contact_name}
                                                        {stop.contact_phone &&
                                                            ` • ${stop.contact_phone}`}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-muted-foreground">
                                    No hay paradas definidas
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* POD Display */}
                {order.pod && (
                    <PodDisplay pod={order.pod} orderType="delivery" />
                )}
            </div>

            {/* Assign Driver Dialog */}
            <Dialog open={assignDialogOpen} onOpenChange={setAssignDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Asignar Conductor</DialogTitle>
                        <DialogDescription>
                            Seleccione un conductor para esta entrega
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
                            disabled={!selectedDriver || isProcessing}
                        >
                            Asignar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* POD Dialog */}
            <PodDialog
                open={podDialogOpen}
                onOpenChange={setPodDialogOpen}
                orderType="delivery"
                orderId={order.id}
            />
        </AppLayout>
    );
}
