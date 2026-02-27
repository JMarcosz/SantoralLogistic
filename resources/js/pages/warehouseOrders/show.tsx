/* eslint-disable react-hooks/set-state-in-effect */
/* eslint-disable @typescript-eslint/no-unused-vars */
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Box,
    CheckCircle,
    Loader2,
    MapPin,
    Package,
    Play,
    Truck,
    XCircle,
} from 'lucide-react';
import { useEffect, useState } from 'react';

interface Line {
    id: number;
    sku: string;
    description: string;
    qty_to_pick: number;
    qty_picked: number;
    uom: string;
    location_code: string | null;
    is_fully_picked: boolean;
    progress: number;
}

interface WarehouseOrderData {
    id: number;
    warehouse: { id: number; name: string; code: string };
    shipping_order: {
        id: number;
        order_number: string;
        customer_name: string | null;
    };
    delivery_order_id: number | null;
    status: string;
    status_label: string;
    status_color: string;
    reference: string | null;
    notes: string | null;
    created_by: string | null;
    created_at: string;
    lines: Line[];
    total_qty_to_pick: number;
    total_qty_picked: number;
    picking_progress: number;
    can_start_picking: boolean;
    can_mark_packed: boolean;
    can_mark_dispatched: boolean;
    can_cancel: boolean;
}

interface PageProps {
    warehouseOrder: WarehouseOrderData;
    flash?: { success?: string; error?: string };
    [key: string]: unknown;
}

const statusColors: Record<string, string> = {
    slate: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
    amber: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
    blue: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
    emerald: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    red: 'bg-red-500/20 text-red-400 border-red-500/30',
};

export default function WarehouseOrderShow({ warehouseOrder }: PageProps) {
    const { flash } = usePage<PageProps>().props;
    const [editingLine, setEditingLine] = useState<Line | null>(null);
    const [pickedQty, setPickedQty] = useState<string>('');
    const [cancelDialogOpen, setCancelDialogOpen] = useState(false);
    const [cancelReason, setCancelReason] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [notification, setNotification] = useState<{
        type: 'success' | 'error';
        message: string;
    } | null>(null);

    // Show flash messages as notifications
    useEffect(() => {
        if (flash?.success) {
            setNotification({ type: 'success', message: flash.success });
        } else if (flash?.error) {
            setNotification({ type: 'error', message: flash.error });
        }
    }, [flash]);

    // Auto-hide notifications
    useEffect(() => {
        if (notification) {
            const timer = setTimeout(() => setNotification(null), 4000);
            return () => clearTimeout(timer);
        }
    }, [notification]);

    const handleStartPicking = () => {
        setIsProcessing(true);
        router.post(
            `/warehouse-orders/${warehouseOrder.id}/start-picking`,
            {},
            { onFinish: () => setIsProcessing(false) },
        );
    };

    const handleMarkPacked = () => {
        setIsProcessing(true);
        router.post(
            `/warehouse-orders/${warehouseOrder.id}/pack`,
            {},
            { onFinish: () => setIsProcessing(false) },
        );
    };

    const handleDispatch = () => {
        if (!confirm('¿Está seguro de despachar esta orden?')) return;
        setIsProcessing(true);
        router.post(
            `/warehouse-orders/${warehouseOrder.id}/dispatch`,
            {},
            { onFinish: () => setIsProcessing(false) },
        );
    };

    const handleCancel = () => {
        setIsProcessing(true);
        router.post(
            `/warehouse-orders/${warehouseOrder.id}/cancel`,
            { reason: cancelReason },
            {
                onFinish: () => {
                    setIsProcessing(false);
                    setCancelDialogOpen(false);
                },
            },
        );
    };

    const openLineEdit = (line: Line) => {
        setEditingLine(line);
        setPickedQty(line.qty_picked.toString());
    };

    const handleLinePick = () => {
        if (!editingLine) return;
        const qty = parseFloat(pickedQty);
        if (isNaN(qty) || qty < 0) {
            setNotification({ type: 'error', message: 'Cantidad inválida' });
            return;
        }
        if (qty > editingLine.qty_to_pick) {
            setNotification({
                type: 'error',
                message: 'La cantidad no puede exceder lo solicitado',
            });
            return;
        }
        setIsProcessing(true);
        router.patch(
            `/warehouse-orders/${warehouseOrder.id}/lines/${editingLine.id}`,
            { qty_picked: qty },
            {
                onFinish: () => {
                    setIsProcessing(false);
                    setEditingLine(null);
                },
            },
        );
    };

    const isPending = warehouseOrder.status === 'pending';
    const isPicking = warehouseOrder.status === 'picking';
    const isPacked = warehouseOrder.status === 'packed';
    const isTerminal =
        warehouseOrder.status === 'dispatched' ||
        warehouseOrder.status === 'cancelled';

    const pendingLines = warehouseOrder.lines.filter(
        (l) => !l.is_fully_picked,
    ).length;
    const completedLines = warehouseOrder.lines.filter(
        (l) => l.is_fully_picked,
    ).length;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Almacén', href: '/warehouse/dashboard' },
                { title: 'Órdenes de Almacén', href: '/warehouse-orders' },
                {
                    title: `Orden #${warehouseOrder.id}`,
                    href: `/warehouse-orders/${warehouseOrder.id}`,
                },
            ]}
        >
            <Head title={`Orden de Almacén #${warehouseOrder.id}`} />

            {/* Notification Toast */}
            {notification && (
                <div
                    className={`fixed top-4 right-4 z-50 animate-in rounded-lg px-4 py-3 shadow-lg fade-in slide-in-from-top-2 ${
                        notification.type === 'success'
                            ? 'bg-emerald-600 text-white'
                            : 'bg-red-600 text-white'
                    }`}
                >
                    <div className="flex items-center gap-2">
                        {notification.type === 'success' ? (
                            <CheckCircle className="h-5 w-5" />
                        ) : (
                            <AlertTriangle className="h-5 w-5" />
                        )}
                        {notification.message}
                    </div>
                </div>
            )}

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/warehouse-orders">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold">
                                    Orden #{warehouseOrder.id}
                                </h1>
                                <Badge
                                    variant="outline"
                                    className={`${statusColors[warehouseOrder.status_color]} px-3 py-1 text-sm`}
                                >
                                    {warehouseOrder.status_label}
                                </Badge>
                            </div>
                            <p className="mt-1 text-muted-foreground">
                                SO: {warehouseOrder.shipping_order.order_number}
                                {warehouseOrder.shipping_order.customer_name &&
                                    ` • ${warehouseOrder.shipping_order.customer_name}`}
                            </p>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-2">
                        {warehouseOrder.can_start_picking && (
                            <Button
                                onClick={handleStartPicking}
                                disabled={isProcessing}
                                className="bg-amber-600 hover:bg-amber-700"
                            >
                                {isProcessing ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <Play className="mr-2 h-4 w-4" />
                                )}
                                Iniciar Picking
                            </Button>
                        )}
                        {warehouseOrder.can_mark_packed && (
                            <Button
                                onClick={handleMarkPacked}
                                disabled={isProcessing}
                                className="bg-blue-600 hover:bg-blue-700"
                            >
                                {isProcessing ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <Package className="mr-2 h-4 w-4" />
                                )}
                                Marcar Empacado
                            </Button>
                        )}
                        {warehouseOrder.can_mark_dispatched && (
                            <Button
                                onClick={handleDispatch}
                                disabled={isProcessing}
                                className="bg-emerald-600 hover:bg-emerald-700"
                            >
                                {isProcessing ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <Truck className="mr-2 h-4 w-4" />
                                )}
                                Despachar
                            </Button>
                        )}
                        {warehouseOrder.can_cancel && (
                            <Button
                                variant="destructive"
                                onClick={() => setCancelDialogOpen(true)}
                                disabled={isProcessing}
                            >
                                <XCircle className="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                        )}
                    </div>
                </div>

                {/* KPIs */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card className="border-blue-500/20 bg-gradient-to-br from-blue-500/10 to-blue-600/5">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Progreso
                                    </p>
                                    <p className="text-3xl font-bold text-blue-400">
                                        {warehouseOrder.picking_progress}%
                                    </p>
                                </div>
                                <Progress
                                    value={warehouseOrder.picking_progress}
                                    className="h-2 w-20"
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-emerald-500/20 bg-gradient-to-br from-emerald-500/10 to-emerald-600/5">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Completados
                                    </p>
                                    <p className="text-3xl font-bold text-emerald-400">
                                        {completedLines}
                                    </p>
                                </div>
                                <CheckCircle className="h-8 w-8 text-emerald-500/50" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-amber-500/20 bg-gradient-to-br from-amber-500/10 to-amber-600/5">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Pendientes
                                    </p>
                                    <p className="text-3xl font-bold text-amber-400">
                                        {pendingLines}
                                    </p>
                                </div>
                                <Box className="h-8 w-8 text-amber-500/50" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-slate-500/20 bg-gradient-to-br from-slate-500/10 to-slate-600/5">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Total Líneas
                                    </p>
                                    <p className="text-3xl font-bold">
                                        {warehouseOrder.lines.length}
                                    </p>
                                </div>
                                <Package className="h-8 w-8 text-slate-500/50" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Info Row */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Almacén
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="font-semibold">
                                {warehouseOrder.warehouse.name}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {warehouseOrder.warehouse.code}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Referencia
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="font-semibold">
                                {warehouseOrder.reference || '-'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Creado
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="font-semibold">
                                {warehouseOrder.created_at}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                por {warehouseOrder.created_by || 'Sistema'}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Lines Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            Líneas de Picking
                        </CardTitle>
                        <CardDescription>
                            {isPicking
                                ? 'Haga clic en una línea para registrar la cantidad pickeada'
                                : 'Detalle de productos a preparar'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>SKU</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead>Ubicación</TableHead>
                                        <TableHead className="text-right">
                                            A Pickear
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Pickeado
                                        </TableHead>
                                        <TableHead className="w-32">
                                            Progreso
                                        </TableHead>
                                        <TableHead className="w-20">
                                            Estado
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {warehouseOrder.lines.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-12 text-center text-muted-foreground"
                                            >
                                                No hay líneas en esta orden
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        warehouseOrder.lines.map((line) => (
                                            <TableRow
                                                key={line.id}
                                                className={`${
                                                    isPicking &&
                                                    !line.is_fully_picked
                                                        ? 'cursor-pointer hover:bg-muted/50'
                                                        : ''
                                                } ${line.is_fully_picked ? 'bg-emerald-500/5' : ''}`}
                                                onClick={() =>
                                                    isPicking &&
                                                    !line.is_fully_picked &&
                                                    openLineEdit(line)
                                                }
                                            >
                                                <TableCell className="font-mono font-medium">
                                                    {line.sku}
                                                </TableCell>
                                                <TableCell>
                                                    {line.description}
                                                </TableCell>
                                                <TableCell>
                                                    {line.location_code ? (
                                                        <span className="flex items-center gap-1 text-sm">
                                                            <MapPin className="h-3 w-3" />
                                                            {line.location_code}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            -
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {line.qty_to_pick}{' '}
                                                    <span className="text-muted-foreground">
                                                        {line.uom}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {line.qty_picked}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Progress
                                                            value={
                                                                line.progress
                                                            }
                                                            className="h-2"
                                                        />
                                                        <span className="w-10 text-xs text-muted-foreground">
                                                            {line.progress}%
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {line.is_fully_picked ? (
                                                        <CheckCircle className="h-5 w-5 text-emerald-500" />
                                                    ) : (
                                                        <Box className="h-5 w-5 text-amber-500" />
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Edit Line Dialog */}
            <Dialog
                open={!!editingLine}
                onOpenChange={() => setEditingLine(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Registrar Picking</DialogTitle>
                        <DialogDescription>
                            {editingLine?.sku} - {editingLine?.description}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="rounded-lg bg-muted/50 p-4">
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">
                                    Cantidad a pickear:
                                </span>
                                <span className="font-bold">
                                    {editingLine?.qty_to_pick}{' '}
                                    {editingLine?.uom}
                                </span>
                            </div>
                            {editingLine?.location_code && (
                                <div className="mt-2 flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Ubicación:
                                    </span>
                                    <span className="font-mono">
                                        {editingLine.location_code}
                                    </span>
                                </div>
                            )}
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Cantidad Pickeada
                            </label>
                            <Input
                                type="number"
                                step="0.01"
                                min="0"
                                max={editingLine?.qty_to_pick}
                                value={pickedQty}
                                onChange={(
                                    e: React.ChangeEvent<HTMLInputElement>,
                                ) => setPickedQty(e.target.value)}
                                className="text-center text-lg"
                                autoFocus
                            />
                        </div>
                        <Button
                            variant="outline"
                            className="w-full"
                            onClick={() =>
                                setPickedQty(
                                    editingLine?.qty_to_pick.toString() || '0',
                                )
                            }
                        >
                            Pickear Todo ({editingLine?.qty_to_pick})
                        </Button>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setEditingLine(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleLinePick}
                            disabled={isProcessing}
                            className="bg-emerald-600 hover:bg-emerald-700"
                        >
                            {isProcessing ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <CheckCircle className="mr-2 h-4 w-4" />
                            )}
                            Guardar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Cancel Dialog */}
            <Dialog open={cancelDialogOpen} onOpenChange={setCancelDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-500">
                            <AlertTriangle className="h-5 w-5" />
                            Cancelar Orden
                        </DialogTitle>
                        <DialogDescription>
                            Esta acción no se puede deshacer. La orden quedará
                            en estado cancelado.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2 py-4">
                        <label className="text-sm font-medium">
                            Razón de cancelación (opcional)
                        </label>
                        <Textarea
                            value={cancelReason}
                            onChange={(
                                e: React.ChangeEvent<HTMLTextAreaElement>,
                            ) => setCancelReason(e.target.value)}
                            placeholder="Indique la razón de la cancelación..."
                            rows={3}
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCancelDialogOpen(false)}
                        >
                            No, mantener
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleCancel}
                            disabled={isProcessing}
                        >
                            {isProcessing && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            Sí, cancelar orden
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
