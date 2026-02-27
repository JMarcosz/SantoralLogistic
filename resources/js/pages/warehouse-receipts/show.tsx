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
import { cn, formatDate } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    CheckCircle,
    Edit,
    Lock,
    Package,
    TrendingDown,
    TrendingUp,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface Customer {
    id: number;
    name: string;
}

interface ReceiptLine {
    id: number;
    item_code: string;
    description: string | null;
    expected_qty: number | null;
    received_qty: number;
    uom: string;
    lot_number: string | null;
    serial_number: string | null;
    expiration_date: string | null;
}

interface Receipt {
    id: number;
    receipt_number: string | null;
    reference: string | null;
    status: string;
    expected_at: string | null;
    received_at: string | null;
    notes: string | null;
    created_at: string;
    warehouse: Warehouse;
    customer: Customer;
    lines: ReceiptLine[];
}

interface Props {
    receipt: Receipt;
    allowedTransitions: string[];
    canEdit: boolean;
}

const STATUS_COLORS: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    received: 'bg-blue-100 text-blue-800',
    closed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const STATUS_LABELS: Record<string, string> = {
    draft: 'Borrador',
    received: 'Recibido',
    closed: 'Cerrado',
    cancelled: 'Cancelado',
};

export default function WarehouseReceiptShow({
    receipt,
    allowedTransitions,
    canEdit,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inicio', href: '/dashboard' },
        { title: 'Recepciones', href: '/warehouse-receipts' },
        {
            title: receipt.receipt_number || `#${receipt.id}`,
            href: `/warehouse-receipts/${receipt.id}`,
        },
    ];

    // Modal states
    const [confirmAction, setConfirmAction] = useState<
        'mark-received' | 'close' | 'cancel' | null
    >(null);
    const [isProcessing, setIsProcessing] = useState(false);

    // Computed stats
    const totalReceived = receipt.lines.reduce(
        (sum, l) => sum + Number(l.received_qty),
        0,
    );
    const linesWithVariance = receipt.lines.filter(
        (l) => l.expected_qty !== null && l.received_qty !== l.expected_qty,
    ).length;

    const handleConfirmAction = () => {
        if (!confirmAction) return;
        setIsProcessing(true);

        const routes: Record<string, string> = {
            'mark-received': `/warehouse-receipts/${receipt.id}/mark-received`,
            close: `/warehouse-receipts/${receipt.id}/close`,
            cancel: `/warehouse-receipts/${receipt.id}/cancel`,
        };

        router.post(routes[confirmAction], undefined, {
            onFinish: () => {
                setIsProcessing(false);
                setConfirmAction(null);
            },
        });
    };

    const getModalConfig = () => {
        switch (confirmAction) {
            case 'mark-received':
                return {
                    title: 'Confirmar Recepción',
                    description:
                        'Esta acción marcará la recepción como recibida y creará los registros de inventario correspondientes. Esta acción no se puede deshacer.',
                    actionLabel: 'Confirmar Recepción',
                    variant: 'default' as const,
                    icon: CheckCircle,
                };
            case 'close':
                return {
                    title: 'Cerrar Recepción',
                    description:
                        'Una vez cerrada, la recepción no podrá ser modificada. ¿Desea continuar?',
                    actionLabel: 'Cerrar',
                    variant: 'default' as const,
                    icon: Lock,
                };
            case 'cancel':
                return {
                    title: 'Cancelar Recepción',
                    description:
                        '¿Está seguro de que desea cancelar esta recepción? Esta acción es irreversible.',
                    actionLabel: 'Cancelar Recepción',
                    variant: 'destructive' as const,
                    icon: AlertTriangle,
                };
            default:
                return null;
        }
    };

    const modalConfig = getModalConfig();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Recepción ${receipt.receipt_number || receipt.id}`} />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/warehouse-receipts">
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                <Package className="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold">
                                        {receipt.receipt_number ||
                                            `Recepción #${receipt.id}`}
                                    </h1>
                                    <Badge
                                        className={
                                            STATUS_COLORS[receipt.status] || ''
                                        }
                                    >
                                        {STATUS_LABELS[receipt.status] ||
                                            receipt.status}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {receipt.customer?.name} •{' '}
                                    {receipt.warehouse?.name}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {canEdit && (
                            <Link
                                href={`/warehouse-receipts/${receipt.id}/edit`}
                            >
                                <Button variant="outline">
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar
                                </Button>
                            </Link>
                        )}
                        {allowedTransitions.includes('mark-received') && (
                            <Button
                                onClick={() =>
                                    setConfirmAction('mark-received')
                                }
                            >
                                <CheckCircle className="mr-2 h-4 w-4" />
                                Marcar Recibido
                            </Button>
                        )}
                        {allowedTransitions.includes('close') && (
                            <Button
                                onClick={() => setConfirmAction('close')}
                                variant="secondary"
                            >
                                <Lock className="mr-2 h-4 w-4" />
                                Cerrar
                            </Button>
                        )}
                        {allowedTransitions.includes('cancel') && (
                            <Button
                                onClick={() => setConfirmAction('cancel')}
                                variant="destructive"
                            >
                                <XCircle className="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Receipt Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información de la Recepción</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Almacén
                                    </p>
                                    <p className="font-medium">
                                        {receipt.warehouse?.name} (
                                        {receipt.warehouse?.code})
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Cliente
                                    </p>
                                    <p className="font-medium">
                                        {receipt.customer?.name}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Referencia
                                    </p>
                                    <p className="font-medium">
                                        {receipt.reference || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Fecha Esperada
                                    </p>
                                    <p className="font-medium">
                                        {formatDate(receipt.expected_at)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Fecha Recibido
                                    </p>
                                    <p className="font-medium">
                                        {formatDate(receipt.received_at)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Creado
                                    </p>
                                    <p className="font-medium">
                                        {formatDate(receipt.created_at)}
                                    </p>
                                </div>
                            </div>
                            {receipt.notes && (
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Notas
                                    </p>
                                    <p className="font-medium">
                                        {receipt.notes}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Summary */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen</CardTitle>
                            <CardDescription>
                                {receipt.lines.length} líneas en esta recepción
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="rounded-lg bg-muted p-4 text-center">
                                    <p className="text-2xl font-bold">
                                        {receipt.lines.length}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        SKUs
                                    </p>
                                </div>
                                <div className="rounded-lg bg-muted p-4 text-center">
                                    <p className="text-2xl font-bold">
                                        {totalReceived.toLocaleString()}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Total Recibido
                                    </p>
                                </div>
                                <div
                                    className={cn(
                                        'rounded-lg p-4 text-center',
                                        linesWithVariance > 0
                                            ? 'bg-amber-50 dark:bg-amber-950'
                                            : 'bg-emerald-50 dark:bg-emerald-950',
                                    )}
                                >
                                    <div className="flex items-center justify-center gap-1">
                                        {linesWithVariance > 0 ? (
                                            <TrendingDown className="h-5 w-5 text-amber-600" />
                                        ) : (
                                            <TrendingUp className="h-5 w-5 text-emerald-600" />
                                        )}
                                        <p className="text-2xl font-bold">
                                            {linesWithVariance}
                                        </p>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Variaciones
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Lines Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Líneas de Recepción</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>SKU</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead className="text-right">
                                        Esperado
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Recibido
                                    </TableHead>
                                    <TableHead>UOM</TableHead>
                                    <TableHead>Lote</TableHead>
                                    <TableHead>Serial</TableHead>
                                    <TableHead>Vencimiento</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {receipt.lines.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No hay líneas
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    receipt.lines.map((line) => (
                                        <TableRow key={line.id}>
                                            <TableCell className="font-medium">
                                                {line.item_code}
                                            </TableCell>
                                            <TableCell>
                                                {line.description || '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {line.expected_qty ?? '-'}
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {line.received_qty}
                                            </TableCell>
                                            <TableCell>{line.uom}</TableCell>
                                            <TableCell>
                                                {line.lot_number || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {line.serial_number || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    line.expiration_date,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            {/* Confirmation Modal */}
            <AlertDialog
                open={confirmAction !== null}
                onOpenChange={(open) => !open && setConfirmAction(null)}
            >
                <AlertDialogContent>
                    {modalConfig && (
                        <>
                            <AlertDialogHeader>
                                <div className="flex items-center gap-3">
                                    <div
                                        className={cn(
                                            'flex h-10 w-10 items-center justify-center rounded-full',
                                            confirmAction === 'cancel'
                                                ? 'bg-destructive/10'
                                                : 'bg-primary/10',
                                        )}
                                    >
                                        <modalConfig.icon
                                            className={cn(
                                                'h-5 w-5',
                                                confirmAction === 'cancel'
                                                    ? 'text-destructive'
                                                    : 'text-primary',
                                            )}
                                        />
                                    </div>
                                    <AlertDialogTitle>
                                        {modalConfig.title}
                                    </AlertDialogTitle>
                                </div>
                                <AlertDialogDescription className="pt-2">
                                    {modalConfig.description}
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel disabled={isProcessing}>
                                    Cancelar
                                </AlertDialogCancel>
                                <AlertDialogAction
                                    onClick={handleConfirmAction}
                                    disabled={isProcessing}
                                    className={
                                        modalConfig.variant === 'destructive'
                                            ? 'bg-destructive hover:bg-destructive/90'
                                            : ''
                                    }
                                >
                                    {isProcessing
                                        ? 'Procesando...'
                                        : modalConfig.actionLabel}
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </>
                    )}
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
