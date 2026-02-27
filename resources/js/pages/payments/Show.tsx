import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    CheckCircle,
    Download,
    Edit,
    FileText,
    Trash2,
} from 'lucide-react';

interface PaymentAllocation {
    id: number;
    invoice_id: number;
    amount_applied: number;
    invoice: {
        id: number;
        number: string;
        ncf?: string;
        total_amount: number;
        currency_code: string;
    };
}

interface Payment {
    id: number;
    payment_number: string;
    type: 'inbound' | 'outbound';
    status: 'draft' | 'posted' | 'voided';
    payment_date: string;
    amount: number;
    currency_code: string;
    exchange_rate: number;
    base_amount: number;
    amount_allocated: number;
    amount_unapplied: number;
    reference?: string;
    notes?: string;
    customer?: {
        id: number;
        name: string;
        fiscal_name?: string;
        tax_id?: string;
    };
    payment_method: {
        id: number;
        name: string;
    };
    allocations: PaymentAllocation[];
    creator: {
        name: string;
    };
    posted_by?: {
        name: string;
    };
    posted_at?: string;
    voider?: {
        name: string;
    };
    voided_at?: string;
    void_reason?: string;
    created_at: string;
}

interface Props {
    payment: Payment;
    can: {
        edit: boolean;
        post: boolean;
        void: boolean;
        delete: boolean;
    };
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    posted: 'bg-green-500/10 text-green-400 border-green-500/30',
    voided: 'bg-red-500/10 text-red-400 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    posted: 'Contabilizado',
    voided: 'Anulado',
};

export default function PaymentShow({ payment, can }: Props) {
    // const { flash } = usePage().props as any; // Flash handled by AppLayout

    const handlePost = () => {
        if (
            confirm(
                '¿Estás seguro de contabilizar este pago? No se podrán hacer cambios posteriores.',
            )
        ) {
            router.post(`/payments/${payment.id}/post`);
        }
    };

    const handleVoid = () => {
        const reason = prompt('Indica la razón de anulación:');
        if (reason) {
            router.post(`/payments/${payment.id}/void`, {
                void_reason: reason,
            });
        }
    };

    const handleDelete = () => {
        if (
            confirm(
                '¿Estás seguro de eliminar este pago? Esta acción no se puede deshacer.',
            )
        ) {
            router.delete(`/payments/${payment.id}`);
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pagos', href: '/payments' },
                {
                    title: payment.payment_number,
                    href: `/payments/${payment.id}`,
                },
            ]}
        >
            <Head title={`Pago ${payment.payment_number}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex gap-4">
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => router.visit('/payments')}
                        >
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {payment.payment_number}
                            </h1>
                            <p className="text-muted-foreground">
                                {payment.type === 'inbound' ? 'Cobro' : 'Pago'}
                            </p>
                        </div>
                        <div>
                            <Badge className={statusColors[payment.status]}>
                                {statusLabels[payment.status]}
                            </Badge>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        {can.edit && (
                            <Button
                                variant="outline"
                                onClick={() =>
                                    router.visit(`/payments/${payment.id}/edit`)
                                }
                            >
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                            </Button>
                        )}
                        {can.post && (
                            <Button onClick={handlePost}>
                                <CheckCircle className="mr-2 h-4 w-4" />
                                Contabilizar
                            </Button>
                        )}
                        {can.void && (
                            <Button variant="destructive" onClick={handleVoid}>
                                <Ban className="mr-2 h-4 w-4" />
                                Anular
                            </Button>
                        )}
                        {can.delete && (
                            <Button
                                variant="destructive"
                                onClick={handleDelete}
                            >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            onClick={() =>
                                window.open(
                                    `/payments/${payment.id}/pdf`,
                                    '_blank',
                                )
                            }
                        >
                            <Download className="mr-2 h-4 w-4" />
                            Descargar Recibo
                        </Button>
                    </div>
                </div>

                {/* Flash Messages handled by AppLayout/Sonner */}

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Content */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Payment Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles del Pago</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">
                                            Cliente
                                        </label>
                                        <p className="font-medium">
                                            {payment.customer?.fiscal_name ||
                                                payment.customer?.name}
                                        </p>
                                        {payment.customer?.tax_id && (
                                            <p className="text-sm text-muted-foreground">
                                                RNC: {payment.customer.tax_id}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">
                                            Fecha de Pago
                                        </label>
                                        <p className="font-medium">
                                            {formatDate(payment.payment_date)}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">
                                            Método de Pago
                                        </label>
                                        <p className="font-medium">
                                            {payment.payment_method.name}
                                        </p>
                                    </div>
                                    {payment.reference && (
                                        <div>
                                            <label className="text-sm font-medium text-muted-foreground">
                                                Referencia
                                            </label>
                                            <p className="font-mono font-medium">
                                                {payment.reference}
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {payment.notes && (
                                    <div>
                                        <label className="text-sm font-medium text-muted-foreground">
                                            Notas
                                        </label>
                                        <p className="mt-1 rounded-md border bg-muted/50 p-3 whitespace-pre-wrap">
                                            {payment.notes}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Allocations */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Aplicación a Facturas</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Factura</TableHead>
                                            <TableHead>NCF</TableHead>
                                            <TableHead className="text-right">
                                                Total Factura
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Monto Aplicado
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Acción
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {payment.allocations.map(
                                            (allocation) => (
                                                <TableRow key={allocation.id}>
                                                    <TableCell className="font-mono">
                                                        {
                                                            allocation.invoice
                                                                .number
                                                        }
                                                    </TableCell>
                                                    <TableCell className="font-mono text-sm">
                                                        {allocation.invoice
                                                            .ncf || '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right font-mono">
                                                        {formatCurrency(
                                                            allocation.invoice
                                                                .total_amount,
                                                            allocation.invoice
                                                                .currency_code,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right font-mono font-semibold">
                                                        {formatCurrency(
                                                            allocation.amount_applied,
                                                            payment.currency_code,
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        <Link
                                                            href={`/invoices/${allocation.invoice.id}`}
                                                            className="text-blue-600 hover:underline"
                                                        >
                                                            <FileText className="inline h-4 w-4" />
                                                        </Link>
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Amount Summary */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Resumen de Montos</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Monto Total:
                                    </span>
                                    <span className="font-mono font-semibold">
                                        {formatCurrency(
                                            payment.amount,
                                            payment.currency_code,
                                        )}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">
                                        Monto Aplicado:
                                    </span>
                                    <span className="font-mono">
                                        {formatCurrency(
                                            payment.amount_allocated,
                                            payment.currency_code,
                                        )}
                                    </span>
                                </div>
                                <div className="flex justify-between border-t pt-3">
                                    <span className="font-medium">
                                        Sin Aplicar:
                                    </span>
                                    <span className="font-mono font-bold">
                                        {formatCurrency(
                                            payment.amount_unapplied,
                                            payment.currency_code,
                                        )}
                                    </span>
                                </div>
                                {payment.currency_code !== 'DOP' && (
                                    <>
                                        <div className="border-t pt-3">
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">
                                                    Tasa de Cambio:
                                                </span>
                                                <span className="font-mono">
                                                    {payment.exchange_rate}
                                                </span>
                                            </div>
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">
                                                    Monto Base (DOP):
                                                </span>
                                                <span className="font-mono">
                                                    {formatCurrency(
                                                        payment.base_amount,
                                                        'DOP',
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Audit Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Información de Auditoría</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div>
                                    <label className="text-muted-foreground">
                                        Creado por:
                                    </label>
                                    <p className="font-medium">
                                        {payment.creator.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {formatDate(payment.created_at)}
                                    </p>
                                </div>

                                {payment.posted_at && payment.posted_by && (
                                    <div className="border-t pt-3">
                                        <label className="text-muted-foreground">
                                            Contabilizado por:
                                        </label>
                                        <p className="font-medium">
                                            {payment.posted_by.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatDate(payment.posted_at)}
                                        </p>
                                    </div>
                                )}

                                {payment.voided_at && payment.voider && (
                                    <div className="border-t pt-3">
                                        <label className="text-muted-foreground">
                                            Anulado por:
                                        </label>
                                        <p className="font-medium">
                                            {payment.voider.name}
                                        </p>
                                        <p className="pb-3 text-xs text-muted-foreground">
                                            {formatDate(payment.voided_at)}
                                        </p>
                                        {payment.void_reason && (
                                            <div className="border-t pt-3">
                                                <p className="font-bold">
                                                    Motivo de anulación:
                                                </p>
                                                <p className="mt-1 text-xs">
                                                    {payment.void_reason}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
