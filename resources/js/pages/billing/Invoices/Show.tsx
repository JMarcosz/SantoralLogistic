/* eslint-disable @typescript-eslint/no-explicit-any */
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    BookOpen,
    FileText,
    Mail,
    Printer,
    XCircle,
} from 'lucide-react';
import { FormEvent, useState } from 'react';

type Customer = {
    id: number;
    name: string;
    fiscal_name: string | null;
    tax_id: string | null;
    tax_id_type: string | null;
    billing_address: string | null;
    country: string | null;
};

type ShippingOrder = {
    id: number;
    so_number: string;
};

type PreInvoice = {
    id: number;
    number: string;
};

type InvoiceLine = {
    id: number;
    code: string;
    description: string;
    qty: number;
    unit_price: number;
    amount: number;
    tax_amount: number;
    currency_code: string;
};

type Invoice = {
    id: number;
    number: string;
    ncf: string;
    ncf_type: string;
    customer_id: number;
    customer: Customer;
    shipping_order: ShippingOrder | null;
    pre_invoice: PreInvoice | null;
    lines: InvoiceLine[];
    issue_date: string;
    due_date: string | null;
    currency_code: string;
    subtotal_amount: number;
    tax_amount: number;
    total_amount: number;
    taxable_amount: number;
    exempt_amount: number;
    status: 'issued' | 'cancelled';
    cancelled_at: string | null;
    cancellation_reason: string | null;
    notes: string | null;
    journal_entry_id: number | null;
};

type Props = {
    invoice: Invoice;
    canCancel: boolean;
    canPrint: boolean;
    canEmail?: boolean;
};

export default function InvoicesShow({
    invoice,
    canCancel,
    canPrint,
    canEmail = true,
}: Props) {
    const { flash } = usePage().props as any;
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [showEmailDialog, setShowEmailDialog] = useState(false);
    const [cancellationReason, setCancellationReason] = useState('');
    const [emailRecipient, setEmailRecipient] = useState('');
    const [emailMessage, setEmailMessage] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturación',
            href: '/invoices',
        },
        {
            title: 'Facturas Fiscales',
            href: '/invoices',
        },
        {
            title: invoice.number,
            href: `/invoices/${invoice.id}`,
        },
    ];

    const handlePrint = () => {
        window.open(`/invoices/${invoice.id}/print`, '_blank');
    };

    const handleCancelSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!cancellationReason.trim()) {
            return;
        }

        setIsSubmitting(true);
        router.post(
            `/invoices/${invoice.id}/cancel`,
            { reason: cancellationReason },
            {
                onSuccess: () => {
                    setShowCancelDialog(false);
                    setCancellationReason('');
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    const formatCurrency = (amount: number, currency: string) => {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-DO', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const handleEmailSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (!emailRecipient.trim()) {
            return;
        }

        setIsSubmitting(true);
        router.post(
            `/invoices/${invoice.id}/email`,
            {
                email: emailRecipient,
                message: emailMessage || null,
            },
            {
                onSuccess: () => {
                    setShowEmailDialog(false);
                    setEmailRecipient('');
                    setEmailMessage('');
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Factura ${invoice.number}`} />

            <div className="space-y-6 p-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4">
                        <p className="text-sm text-green-800">
                            {flash.success}
                        </p>
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4">
                        <p className="text-sm text-red-800">{flash.error}</p>
                    </div>
                )}

                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="space-y-1">
                        <div className="flex items-center gap-4">
                            <h1 className="text-3xl font-bold tracking-tight">
                                Factura Fiscal
                            </h1>
                            <Badge
                                variant={
                                    invoice.status === 'issued'
                                        ? 'default'
                                        : 'destructive'
                                }
                                className="text-sm"
                            >
                                {invoice.status === 'issued'
                                    ? 'Emitida'
                                    : 'Cancelada'}
                            </Badge>
                        </div>
                        <p className="text-muted-foreground">
                            #{invoice.number} · NCF: {invoice.ncf}
                        </p>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a href="/invoices">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver al listado
                            </a>
                        </Button>
                        {canPrint && (
                            <Button onClick={handlePrint}>
                                <Printer className="mr-2 h-4 w-4" />
                                Imprimir PDF
                            </Button>
                        )}
                        {canEmail && (
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setEmailRecipient('');
                                    setShowEmailDialog(true);
                                }}
                            >
                                <Mail className="mr-2 h-4 w-4" />
                                Enviar por Email
                            </Button>
                        )}
                        {invoice.journal_entry_id && (
                            <Button variant="outline" asChild>
                                <a
                                    href={`/accounting/journal-entries/${invoice.journal_entry_id}`}
                                >
                                    <BookOpen className="mr-2 h-4 w-4" />
                                    Ver Asiento Contable
                                </a>
                            </Button>
                        )}
                        {canCancel && invoice.status === 'issued' && (
                            <Button
                                variant="destructive"
                                onClick={() => setShowCancelDialog(true)}
                            >
                                <XCircle className="mr-2 h-4 w-4" />
                                Cancelar Factura
                            </Button>
                        )}
                    </div>
                </div>

                {/* Cancellation Notice */}
                {invoice.status === 'cancelled' && (
                    <Card className="border-destructive bg-destructive/5">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-destructive">
                                <AlertTriangle className="h-5 w-5" />
                                Factura Cancelada
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div>
                                    <span className="font-semibold">
                                        Fecha de cancelación:
                                    </span>{' '}
                                    {invoice.cancelled_at &&
                                        formatDate(invoice.cancelled_at)}
                                </div>
                                <div>
                                    <span className="font-semibold">
                                        Motivo:
                                    </span>{' '}
                                    {invoice.cancellation_reason}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* NCF Card */}
                <Card className="border-primary bg-primary/5">
                    <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Número de Comprobante Fiscal
                                </p>
                                <p className="font-mono text-2xl font-bold text-primary">
                                    {invoice.ncf}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-sm text-muted-foreground">
                                    Tipo
                                </p>
                                <p className="text-lg font-bold">
                                    {invoice.ncf_type}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Customer Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos del Cliente</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Nombre Fiscal
                                </p>
                                <p className="font-semibold">
                                    {invoice.customer.fiscal_name ||
                                        invoice.customer.name}
                                </p>
                            </div>
                            {invoice.customer.tax_id_type &&
                                invoice.customer.tax_id && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            {invoice.customer.tax_id_type}
                                        </p>
                                        <p className="font-mono">
                                            {invoice.customer.tax_id}
                                        </p>
                                    </div>
                                )}
                            {invoice.customer.billing_address && (
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Dirección
                                    </p>
                                    <p>{invoice.customer.billing_address}</p>
                                </div>
                            )}
                            {invoice.customer.country && (
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        País
                                    </p>
                                    <p>{invoice.customer.country}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Invoice Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles de la Factura</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Fecha de Emisión
                                </p>
                                <p className="font-semibold">
                                    {formatDate(invoice.issue_date)}
                                </p>
                            </div>
                            {invoice.due_date && (
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Fecha de Vencimiento
                                    </p>
                                    <p>{formatDate(invoice.due_date)}</p>
                                </div>
                            )}
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Moneda
                                </p>
                                <p>{invoice.currency_code}</p>
                            </div>
                            {invoice.pre_invoice && (
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Pre-Factura
                                    </p>
                                    <p className="font-mono">
                                        {invoice.pre_invoice.number}
                                    </p>
                                </div>
                            )}
                            {invoice.shipping_order && (
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Orden de Envío
                                    </p>
                                    <p className="font-mono">
                                        {invoice.shipping_order.so_number}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Line Items */}
                <Card>
                    <CardHeader>
                        <CardTitle>Líneas de Factura</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Código</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="text-right">
                                            Cantidad
                                        </TableHead>
                                        <TableHead className="text-right">
                                            P. Unitario
                                        </TableHead>
                                        <TableHead className="text-right">
                                            ITBIS
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Importe
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoice.lines.map((line) => (
                                        <TableRow key={line.id}>
                                            <TableCell className="font-mono">
                                                {line.code}
                                            </TableCell>
                                            <TableCell>
                                                {line.description}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {line.qty}
                                            </TableCell>
                                            <TableCell className="text-right font-mono">
                                                {formatCurrency(
                                                    line.unit_price,
                                                    line.currency_code,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right font-mono">
                                                {formatCurrency(
                                                    line.tax_amount,
                                                    line.currency_code,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right font-mono font-semibold">
                                                {formatCurrency(
                                                    line.amount,
                                                    line.currency_code,
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Totals */}
                <Card>
                    <CardHeader>
                        <CardTitle>Totales</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-2">
                            <div className="flex justify-between text-sm">
                                <span>Subtotal:</span>
                                <span className="font-mono">
                                    {formatCurrency(
                                        invoice.subtotal_amount,
                                        invoice.currency_code,
                                    )}
                                </span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span>ITBIS (18%):</span>
                                <span className="font-mono">
                                    {formatCurrency(
                                        invoice.tax_amount,
                                        invoice.currency_code,
                                    )}
                                </span>
                            </div>
                            {invoice.taxable_amount > 0 && (
                                <div className="flex justify-between text-sm text-muted-foreground">
                                    <span>Monto Gravado:</span>
                                    <span className="font-mono">
                                        {formatCurrency(
                                            invoice.taxable_amount,
                                            invoice.currency_code,
                                        )}
                                    </span>
                                </div>
                            )}
                            {invoice.exempt_amount > 0 && (
                                <div className="flex justify-between text-sm text-muted-foreground">
                                    <span>Monto Exento:</span>
                                    <span className="font-mono">
                                        {formatCurrency(
                                            invoice.exempt_amount,
                                            invoice.currency_code,
                                        )}
                                    </span>
                                </div>
                            )}
                            <div className="flex justify-between border-t pt-2 text-lg font-bold">
                                <span>Total:</span>
                                <span className="font-mono text-primary">
                                    {formatCurrency(
                                        invoice.total_amount,
                                        invoice.currency_code,
                                    )}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes */}
                {invoice.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Notas
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm whitespace-pre-wrap">
                                {invoice.notes}
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Cancel Dialog */}
            <Dialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
                <DialogContent>
                    <form onSubmit={handleCancelSubmit}>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2 text-destructive">
                                <AlertTriangle className="h-5 w-5" />
                                Cancelar Factura Fiscal
                            </DialogTitle>
                            <DialogDescription>
                                Esta acción es <strong>irreversible</strong>. La
                                factura será marcada como cancelada y se
                                incluirá en el reporte DGII 608. El NCF se
                                mantendrá pero no podrá ser utilizado
                                nuevamente.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="reason">
                                    Motivo de Cancelación *
                                </Label>
                                <Textarea
                                    id="reason"
                                    placeholder="Describa el motivo de la cancelación..."
                                    value={cancellationReason}
                                    onChange={(e) =>
                                        setCancellationReason(e.target.value)
                                    }
                                    required
                                    maxLength={500}
                                    rows={4}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {cancellationReason.length}/500 caracteres
                                </p>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowCancelDialog(false)}
                                disabled={isSubmitting}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={
                                    !cancellationReason.trim() || isSubmitting
                                }
                            >
                                {isSubmitting
                                    ? 'Cancelando...'
                                    : 'Confirmar Cancelación'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Email Dialog */}
            <Dialog open={showEmailDialog} onOpenChange={setShowEmailDialog}>
                <DialogContent>
                    <form onSubmit={handleEmailSubmit}>
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <Mail className="h-5 w-5" />
                                Enviar Factura por Email
                            </DialogTitle>
                            <DialogDescription>
                                Se enviará la factura en formato PDF adjunto al
                                correo. El destinatario recibirá el NCF y todos
                                los detalles fiscales.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="email">
                                    Email Destinatario *
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="cliente@example.com"
                                    value={emailRecipient}
                                    onChange={(e) =>
                                        setEmailRecipient(e.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="message">
                                    Mensaje Adicional (Opcional)
                                </Label>
                                <Textarea
                                    id="message"
                                    placeholder="Mensaje personalizado para incluir en el email..."
                                    value={emailMessage}
                                    onChange={(e) =>
                                        setEmailMessage(e.target.value)
                                    }
                                    maxLength={1000}
                                    rows={4}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {emailMessage.length}/1000 caracteres
                                </p>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowEmailDialog(false)}
                                disabled={isSubmitting}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="submit"
                                disabled={
                                    !emailRecipient.trim() || isSubmitting
                                }
                            >
                                {isSubmitting ? 'Enviando...' : 'Enviar Email'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
