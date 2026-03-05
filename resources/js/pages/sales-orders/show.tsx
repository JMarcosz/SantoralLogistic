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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Ban,
    Check,
    CheckCircle,
    FileText,
    Package,
    Truck,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

interface SalesOrderLine {
    id: number;
    product_service?: { id: number; code: string; name: string };
    line_type: 'product' | 'service';
    description: string | null;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_rate: number;
    line_total: number;
}

interface SalesOrder {
    id: number;
    order_number: string;
    quote_id: number | null;
    quote?: { id: number; quote_number: string };
    customer?: { id: number; name: string; code: string | null };
    currency?: { id: number; code: string; symbol: string };
    status: string;
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    confirmed_at: string | null;
    delivered_at: string | null;
    notes: string | null;
    created_at: string;
    lines?: SalesOrderLine[];
}

interface Props {
    order: SalesOrder;
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    confirmed: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    delivering: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    delivered: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    invoiced: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    delivering: 'En Entrega',
    delivered: 'Entregada',
    invoiced: 'Facturada',
    cancelled: 'Cancelada',
};

export default function SalesOrderShow({ order }: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            { title: 'Órdenes de Pedido', href: '/sales-orders' },
            {
                title: order.order_number,
                href: `/sales-orders/${order.id}`,
            },
        ],
        [order],
    );

    const currencySymbol = order.currency?.symbol || '$';

    const [confirmDialog, setConfirmDialog] = useState<{
        open: boolean;
        action: string;
        title: string;
        description: string;
    }>({
        open: false,
        action: '',
        title: '',
        description: '',
    });

    const actionConfigs: Record<
        string,
        { title: string; description: string }
    > = {
        confirm: {
            title: '¿Confirmar orden de pedido?',
            description:
                'Se reservará el inventario disponible para los productos de esta orden.',
        },
        'start-delivery': {
            title: '¿Iniciar entrega?',
            description:
                'La orden pasará a estado "En Entrega".',
        },
        'mark-delivered': {
            title: '¿Marcar como entregada?',
            description:
                'Se descontará el inventario físico y la orden se marcará como entregada.',
        },
        cancel: {
            title: '¿Cancelar orden?',
            description:
                'Se liberarán las reservas de inventario. Esta acción no se puede deshacer.',
        },
    };

    const openConfirmDialog = (action: string) => {
        const config = actionConfigs[action] || {
            title: '¿Confirmar acción?',
            description: '',
        };
        setConfirmDialog({
            open: true,
            action,
            title: config.title,
            description: config.description,
        });
    };

    const executeAction = () => {
        if (!confirmDialog.action) return;

        router.post(
            `/sales-orders/${order.id}/${confirmDialog.action}`,
            {},
            {
                preserveScroll: true,
                onFinish: () =>
                    setConfirmDialog({ ...confirmDialog, open: false }),
            },
        );
    };

    const formatDate = (date: string | null) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('es-DO', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    };

    const formatNumber = (num: number | null) => {
        if (num === null || num === undefined) return '-';
        return Number(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
        });
    };

    // Product lines
    const productLines = order.lines?.filter((l) => l.line_type === 'product') || [];
    const serviceLines = order.lines?.filter((l) => l.line_type === 'service') || [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Orden de Pedido ${order.order_number}`} />

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
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-indigo-600 shadow-lg shadow-indigo-600/50">
                                <Package className="h-7 w-7 text-white" />
                            </div>
                            <div>
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl font-bold tracking-tight">
                                        {order.order_number}
                                    </h1>
                                    <Badge
                                        className={statusColors[order.status]}
                                    >
                                        {statusLabels[order.status] ||
                                            order.status}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Creada el {formatDate(order.created_at)}
                                </p>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-2">
                            {/* Confirm */}
                            {order.status === 'draft' && (
                                <Button
                                    className="bg-blue-600 hover:bg-blue-700"
                                    onClick={() => openConfirmDialog('confirm')}
                                >
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Confirmar Orden
                                </Button>
                            )}

                            {/* Start Delivery */}
                            {order.status === 'confirmed' && (
                                <Button
                                    className="bg-amber-600 hover:bg-amber-700"
                                    onClick={() => openConfirmDialog('start-delivery')}
                                >
                                    <Truck className="mr-2 h-4 w-4" />
                                    Iniciar Entrega
                                </Button>
                            )}

                            {/* Mark Delivered */}
                            {order.status === 'delivering' && (
                                <Button
                                    className="bg-emerald-600 hover:bg-emerald-700"
                                    onClick={() => openConfirmDialog('mark-delivered')}
                                >
                                    <Check className="mr-2 h-4 w-4" />
                                    Marcar Entregada
                                </Button>
                            )}

                            {/* Cancel */}
                            {(order.status === 'draft' || order.status === 'confirmed') && (
                                <Button
                                    variant="destructive"
                                    onClick={() => openConfirmDialog('cancel')}
                                >
                                    <Ban className="mr-2 h-4 w-4" />
                                    Cancelar
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Source Quote */}
                    {order.quote && (
                        <div className="mt-4 rounded-lg border border-slate-500/30 bg-slate-500/10 p-3 text-sm">
                            <span className="text-slate-400">
                                Origen: Cotización{' '}
                                <Link
                                    href={`/quotes/${order.quote.id}`}
                                    className="font-semibold underline hover:no-underline"
                                >
                                    {order.quote.quote_number}
                                </Link>
                            </span>
                        </div>
                    )}

                    {/* Flow indicator */}
                    <div className="mt-4 flex items-center gap-2 text-xs text-muted-foreground">
                        <span className={order.quote ? 'text-emerald-400 font-medium' : ''}>Cotización</span>
                        <ArrowRight className="h-3 w-3" />
                        <span className="text-primary font-semibold">Orden de Pedido</span>
                        <ArrowRight className="h-3 w-3" />
                        <span className={order.status === 'delivered' || order.status === 'invoiced' ? 'text-emerald-400 font-medium' : ''}>Entrega</span>
                        <ArrowRight className="h-3 w-3" />
                        <span className={order.status === 'invoiced' ? 'text-emerald-400 font-medium' : ''}>Factura</span>
                    </div>
                </div>

                {/* Info Grid */}
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Cliente</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-lg font-semibold">
                                {order.customer?.name || '-'}
                            </p>
                            {order.customer?.code && (
                                <p className="text-sm text-muted-foreground">
                                    Código: {order.customer.code}
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Detalles</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-muted-foreground">Moneda</p>
                                    <p className="font-medium">
                                        {order.currency?.code} ({currencySymbol})
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">Confirmada</p>
                                    <p className="font-medium">
                                        {formatDate(order.confirmed_at)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">Entregada</p>
                                    <p className="font-medium">
                                        {formatDate(order.delivered_at)}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Product Lines */}
                {productLines.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <Package className="h-5 w-5" />
                                Productos
                            </CardTitle>
                            <CardDescription>
                                {productLines.length} producto(s)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Producto</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="text-right">Cantidad</TableHead>
                                        <TableHead className="text-right">Precio Unit.</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {productLines.map((line, i) => (
                                        <TableRow key={line.id}>
                                            <TableCell className="text-muted-foreground">{i + 1}</TableCell>
                                            <TableCell className="font-medium">{line.product_service?.name || '-'}</TableCell>
                                            <TableCell className="text-muted-foreground">{line.description || '-'}</TableCell>
                                            <TableCell className="text-right">{formatNumber(line.quantity)}</TableCell>
                                            <TableCell className="text-right font-mono">{currencySymbol}{formatNumber(line.unit_price)}</TableCell>
                                            <TableCell className="text-right font-mono font-semibold">{currencySymbol}{formatNumber(line.line_total)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Service Lines */}
                {serviceLines.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Servicios
                            </CardTitle>
                            <CardDescription>
                                {serviceLines.length} servicio(s)
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Servicio</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="text-right">Cantidad</TableHead>
                                        <TableHead className="text-right">Precio Unit.</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {serviceLines.map((line, i) => (
                                        <TableRow key={line.id}>
                                            <TableCell className="text-muted-foreground">{i + 1}</TableCell>
                                            <TableCell className="font-medium">{line.product_service?.name || '-'}</TableCell>
                                            <TableCell className="text-muted-foreground">{line.description || '-'}</TableCell>
                                            <TableCell className="text-right">{formatNumber(line.quantity)}</TableCell>
                                            <TableCell className="text-right font-mono">{currencySymbol}{formatNumber(line.unit_price)}</TableCell>
                                            <TableCell className="text-right font-mono font-semibold">{currencySymbol}{formatNumber(line.line_total)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Totals */}
                <Card>
                    <CardContent className="py-6">
                        <div className="flex justify-end">
                            <div className="w-72 space-y-2 rounded-lg border bg-muted/50 p-4">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal:</span>
                                    <span className="font-mono">
                                        {currencySymbol}{formatNumber(order.subtotal)}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">ITBIS:</span>
                                    <span className="font-mono">
                                        {currencySymbol}{formatNumber(order.tax_amount)}
                                    </span>
                                </div>
                                <div className="border-t pt-2">
                                    <div className="flex justify-between text-lg font-bold">
                                        <span>Total:</span>
                                        <span className="font-mono text-primary">
                                            {currencySymbol}{formatNumber(order.total_amount)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes */}
                {order.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Notas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                {order.notes}
                            </p>
                        </CardContent>
                    </Card>
                )}
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
                            <span className="mt-2 block font-semibold text-foreground">
                                {order.order_number}
                            </span>
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
