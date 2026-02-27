/* eslint-disable @typescript-eslint/no-explicit-any */
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
import preInvoiceRoutes from '@/routes/pre-invoices';
import shippingOrderRoutes from '@/routes/shipping-orders';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Ban, CheckCircle, FileText, Printer } from 'lucide-react';

interface PreInvoiceLine {
    id: number;
    code: string;
    description: string;
    qty: number;
    unit_price: number;
    amount: number;
    tax_amount: number;
    currency_code: string;
}

interface PreInvoice {
    id: number;
    number: string;
    status: 'draft' | 'issued' | 'cancelled';
    issue_date: string;
    due_date?: string;
    currency_code: string;
    subtotal_amount: number;
    tax_amount: number;
    total_amount: number;
    notes?: string;
    invoiced_at?: string;
    customer?: {
        name: string;
        tax_id?: string;
        billing_address?: string;
    };
    shipping_order?: {
        id: number;
        order_number: string;
    };
    lines: PreInvoiceLine[];
}

interface Props {
    preInvoice: PreInvoice;
    can: {
        recordPayment: boolean;
        approvePayment: boolean;
        voidPayment: boolean;
        generateInvoice: boolean;
    };
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    issued: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/30',
};

export default function PreInvoiceShow({ preInvoice, can }: Props) {
    const { flash } = usePage().props as any;

    const handleIssue = () => {
        if (
            confirm(
                '¿Estás seguro de emitir esta factura? No se podrán hacer cambios posteriores.',
            )
        ) {
            router.post(preInvoiceRoutes.issue(preInvoice.id).url);
        }
    };

    const handleCancel = () => {
        if (
            confirm(
                '¿Estás seguro de cancelar esta factura? Esta acción no se puede deshacer.',
            )
        ) {
            router.post(preInvoiceRoutes.cancel(preInvoice.id).url);
        }
    };

    const handleGenerateInvoice = () => {
        if (
            confirm(
                '¿Estás seguro de generar la factura fiscal con NCF? Esta acción no se puede deshacer.',
            )
        ) {
            router.post(
                `/pre-invoices/${preInvoice.id}/generate-invoice`,
                {},
                {
                    preserveScroll: true,
                },
            );
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pre-Facturas', href: preInvoiceRoutes.index().url },
                {
                    title: preInvoice.number,
                    href: preInvoiceRoutes.show(preInvoice.id).url,
                },
            ]}
        >
            <Head title={`Pre-Factura ${preInvoice.number}`} />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Actions Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={preInvoiceRoutes.index().url}>
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                {preInvoice.number}
                            </h1>
                            <div className="mt-1 flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">
                                    Fecha:{' '}
                                    {new Date(
                                        preInvoice.issue_date,
                                    ).toLocaleDateString()}
                                </span>
                                <Badge
                                    className={statusColors[preInvoice.status]}
                                >
                                    {preInvoice.status.toUpperCase()}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={preInvoiceRoutes.print(preInvoice.id).url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Printer className="mr-2 h-4 w-4" />
                                Imprimir / PDF
                            </a>
                        </Button>

                        {preInvoice.status === 'draft' && (
                            <Button
                                onClick={handleIssue}
                                className="bg-emerald-600 hover:bg-emerald-700"
                            >
                                <CheckCircle className="mr-2 h-4 w-4" />
                                Emitir Factura
                            </Button>
                        )}

                        {preInvoice.status === 'issued' &&
                            !preInvoice.invoiced_at &&
                            can.generateInvoice && (
                                <Button
                                    onClick={handleGenerateInvoice}
                                    className="bg-blue-600 hover:bg-blue-700"
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    Generar Factura Fiscal
                                </Button>
                            )}

                        {preInvoice.status !== 'cancelled' && (
                            <Button
                                variant="destructive"
                                onClick={handleCancel}
                            >
                                <Ban className="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                        )}
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-4 text-emerald-500">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-lg border border-red-500/20 bg-red-500/10 p-4 text-red-500">
                        {flash.error}
                    </div>
                )}

                <div className="grid gap-6 md:grid-cols-3">
                    {/* Main Content */}
                    <div className="space-y-6 md:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Detalle de Cargos
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Concepto</TableHead>
                                            <TableHead className="text-right">
                                                Cant.
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Precio
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {preInvoice.lines.map((line) => (
                                            <TableRow key={line.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {line.code}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {line.description}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {line.qty}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {Number(
                                                        line.unit_price,
                                                    ).toFixed(2)}
                                                </TableCell>
                                                <TableCell className="text-right font-bold">
                                                    {Number(
                                                        line.amount,
                                                    ).toFixed(2)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar Info */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Cliente
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="font-bold">
                                    {preInvoice.customer?.name}
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    {preInvoice.customer?.tax_id && (
                                        <p>RNC: {preInvoice.customer.tax_id}</p>
                                    )}
                                    <p>
                                        {preInvoice.customer?.billing_address}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">
                                    Totales
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between text-sm">
                                    <span>Subtotal</span>
                                    <span>
                                        {preInvoice.currency_code}{' '}
                                        {Number(
                                            preInvoice.subtotal_amount,
                                        ).toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                        })}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span>Impuestos</span>
                                    <span>
                                        {preInvoice.currency_code}{' '}
                                        {Number(
                                            preInvoice.tax_amount,
                                        ).toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                        })}
                                    </span>
                                </div>
                                <div className="flex justify-between border-t pt-2 text-lg font-bold">
                                    <span>Total</span>
                                    <span>
                                        {preInvoice.currency_code}{' '}
                                        {Number(
                                            preInvoice.total_amount,
                                        ).toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                        })}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {preInvoice.shipping_order && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">
                                        Referencia
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Link
                                        href={
                                            shippingOrderRoutes.show(
                                                preInvoice.shipping_order.id,
                                            ).url
                                        }
                                        className="flex items-center gap-2 text-primary hover:underline"
                                    >
                                        <FileText className="h-4 w-4" />
                                        Orden{' '}
                                        {preInvoice.shipping_order.order_number}
                                    </Link>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
