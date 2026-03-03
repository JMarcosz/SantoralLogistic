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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
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
import { type BreadcrumbItem, type Company, type Quote } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Check,
    CheckCircle,
    Edit,
    FileText,
    Plane,
    Printer,
    Send,
    Ship,
    Truck,
    X,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';

interface Props {
    quote: Quote;
    company: Company | null;
    shippingOrder: { id: number; order_number: string } | null;
    can: {
        update: boolean;
        delete: boolean;
        send: boolean;
        approve: boolean;
        reject: boolean;
        convert: boolean;
    };
}

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
    AIR: <Plane className="h-5 w-5" />,
    OCEAN: <Ship className="h-5 w-5" />,
    GROUND: <Truck className="h-5 w-5" />,
};

export default function QuoteShow({
    quote,
    company: _company,
    shippingOrder,
    can,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            { title: 'Cotizaciones', href: '/quotes' },
            { title: quote.quote_number, href: `/quotes/${quote.id}` },
        ],
        [quote],
    );

    const currencySymbol = quote.currency?.symbol || '$';

    // Print Dialog State
    const [printModalOpen, setPrintModalOpen] = useState(false);
    const [printLang, setPrintLang] = useState<'es' | 'en'>('es');
    const [printMode, setPrintMode] = useState<
        'standard' | 'detailed' | 'simple'
    >('standard');

    // Confirmation dialog state
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
            `/quotes/${quote.id}/${confirmDialog.action}`,
            {},
            {
                preserveScroll: true,
                onFinish: () =>
                    setConfirmDialog({ ...confirmDialog, open: false }),
            },
        );
    };

    const handlePrint = () => {
        const url = `/quotes/${quote.id}/print?lang=${printLang}&mode=${printMode}`;
        window.open(url, '_blank');
        setPrintModalOpen(false);
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Cotización ${quote.quote_number}`} />

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
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl font-bold tracking-tight">
                                        {quote.quote_number}
                                    </h1>
                                    <Badge
                                        className={statusColors[quote.status]}
                                    >
                                        {statusLabels[quote.status] ||
                                            quote.status}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Creada el {formatDate(quote.created_at)}
                                </p>
                            </div>
                        </div>

                        <div className="flex gap-2">
                            {/* Print Button */}
                            <Button
                                variant="outline"
                                onClick={() => setPrintModalOpen(true)}
                            >
                                <Printer className="mr-2 h-4 w-4" />
                                Imprimir / Descargar
                            </Button>

                            {/* Edit Button */}
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link href={`/quotes/${quote.id}/edit`}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Link>
                                </Button>
                            )}

                            {/* Send Button */}
                            {can.send && (
                                <Button
                                    onClick={() => openConfirmDialog('send')}
                                >
                                    <Send className="mr-2 h-4 w-4" />
                                    Enviar
                                </Button>
                            )}

                            {/* Approve/Reject Buttons */}
                            {can.approve && (
                                <Button
                                    variant="default"
                                    className="bg-emerald-600 hover:bg-emerald-700"
                                    onClick={() => openConfirmDialog('approve')}
                                >
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Aprobar
                                </Button>
                            )}
                            {can.reject && (
                                <Button
                                    variant="destructive"
                                    onClick={() => openConfirmDialog('reject')}
                                >
                                    <XCircle className="mr-2 h-4 w-4" />
                                    Rechazar
                                </Button>
                            )}

                            {/* Convert Button */}
                            {can.convert && quote.status === 'approved' && !shippingOrder && (
                                <Button
                                    variant="default"
                                    onClick={() =>
                                        openConfirmDialog(
                                            'convert-to-shipping-order',
                                        )
                                    }
                                >
                                    <Ship className="mr-2 h-4 w-4" />
                                    Convertir a Orden
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Shipping Order Link */}
                    {shippingOrder && (
                        <div className="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm">
                            <span className="text-emerald-400">
                                ✓ Convertida a Orden de Envío:{' '}
                                <span className="font-semibold">
                                    {shippingOrder.order_number}
                                </span>
                            </span>
                        </div>
                    )}
                </div>

                {/* Info Grid */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Customer Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Cliente</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <p className="text-lg font-semibold">
                                    {quote.customer?.name}
                                </p>
                                {quote.customer?.code && (
                                    <p className="text-sm text-muted-foreground">
                                        Código: {quote.customer.code}
                                    </p>
                                )}
                                {quote.customer?.tax_id && (
                                    <p className="text-sm text-muted-foreground">
                                        RNC: {quote.customer.tax_id}
                                    </p>
                                )}
                            </div>
                            {quote.contact && (
                                <div className="border-t pt-3">
                                    <p className="text-sm font-medium">
                                        Contacto:
                                    </p>
                                    <p className="text-sm">
                                        {quote.contact.name}
                                    </p>
                                    {quote.contact.email && (
                                        <p className="text-sm text-muted-foreground">
                                            {quote.contact.email}
                                        </p>
                                    )}
                                    {quote.contact.phone && (
                                        <p className="text-sm text-muted-foreground">
                                            {quote.contact.phone}
                                        </p>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Quote Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Detalles</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-muted-foreground">
                                        Moneda
                                    </p>
                                    <p className="font-medium">
                                        {quote.currency?.code} ({currencySymbol}
                                        )
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Válido hasta
                                    </p>
                                    <p className="font-medium">
                                        {formatDate(quote.valid_until)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Modo
                                    </p>
                                    <div className="flex items-center gap-2">
                                        {modeIcons[
                                            quote.transport_mode?.code || ''
                                        ] || <Plane className="h-4 w-4" />}
                                        <span className="font-medium">
                                            {quote.transport_mode?.name}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Servicio
                                    </p>
                                    <p className="font-medium">
                                        {quote.service_type?.name}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Lane */}
                <Card>
                    <CardContent className="py-6">
                        <div className="flex items-center justify-center gap-6 text-center">
                            <div>
                                <p className="text-2xl font-bold text-primary">
                                    {quote.origin_port?.code}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {quote.origin_port?.name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {quote.origin_port?.country}
                                </p>
                            </div>
                            <ArrowRight className="h-8 w-8 text-muted-foreground" />
                            <div>
                                <p className="text-2xl font-bold text-primary">
                                    {quote.destination_port?.code}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {quote.destination_port?.name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {quote.destination_port?.country}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Cargo Details */}
                {(quote.total_pieces ||
                    quote.total_weight_kg ||
                    quote.total_volume_cbm) && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">
                                Detalles de Carga
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-6 text-center">
                                {quote.total_pieces && (
                                    <div>
                                        <p className="text-2xl font-bold">
                                            {quote.total_pieces}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Piezas
                                        </p>
                                    </div>
                                )}
                                {quote.total_weight_kg && (
                                    <div>
                                        <p className="text-2xl font-bold">
                                            {formatNumber(
                                                quote.total_weight_kg,
                                            )}{' '}
                                            kg
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Peso
                                        </p>
                                    </div>
                                )}
                                {quote.total_volume_cbm && (
                                    <div>
                                        <p className="text-2xl font-bold">
                                            {formatNumber(
                                                quote.total_volume_cbm,
                                            )}{' '}
                                            CBM
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            Volumen
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Lines */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">
                            Líneas de Cotización
                        </CardTitle>
                        <CardDescription>
                            {quote.lines?.length || 0} líneas
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[50px]">
                                        #
                                    </TableHead>
                                    <TableHead>Producto/Servicio</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead className="text-right">
                                        Cantidad
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Precio Unit.
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {quote.lines?.map((line, index) => (
                                    <TableRow key={line.id}>
                                        <TableCell className="text-muted-foreground">
                                            {index + 1}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {line.product_service?.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {line.description || '-'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatNumber(line.quantity)}
                                        </TableCell>
                                        <TableCell className="text-right font-mono">
                                            {currencySymbol}
                                            {formatNumber(line.unit_price)}
                                        </TableCell>
                                        <TableCell className="text-right font-mono font-semibold">
                                            {currencySymbol}
                                            {formatNumber(line.line_total)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Totals */}
                        <div className="mt-6 flex justify-end">
                            <div className="w-64 space-y-2 rounded-lg border bg-muted/50 p-4">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        Subtotal:
                                    </span>
                                    <span className="font-mono">
                                        {currencySymbol}
                                        {formatNumber(quote.subtotal)}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        ITBIS:
                                    </span>
                                    <span className="font-mono">
                                        {currencySymbol}
                                        {formatNumber(quote.tax_amount)}
                                    </span>
                                </div>
                                <div className="border-t pt-2">
                                    <div className="flex justify-between text-lg font-bold">
                                        <span>Total:</span>
                                        <span className="font-mono text-primary">
                                            {currencySymbol}
                                            {formatNumber(quote.total_amount)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Notes & Terms */}
                {(quote.notes || quote.payment_terms || quote.footer_terms) && (
                    <div className="grid gap-6 lg:grid-cols-2">
                        {quote.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">
                                        Notas
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                        {quote.notes}
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                        {(quote.payment_terms || quote.footer_terms) && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">
                                        Términos
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {quote.payment_terms && (
                                        <div>
                                            <p className="text-sm font-medium">
                                                Términos de Pago:
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {quote.payment_terms.name}
                                            </p>
                                        </div>
                                    )}
                                    {quote.footer_terms && (
                                        <div>
                                            <p className="text-sm font-medium">
                                                Términos de Cotización:
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {quote.footer_terms.name}
                                            </p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>

            {/* Print Options Dialog */}
            <Dialog open={printModalOpen} onOpenChange={setPrintModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Imprimir Cotización</DialogTitle>
                        <DialogDescription>
                            Seleccione las opciones para generar el PDF.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label>Idioma</Label>
                            <Select
                                value={printLang}
                                onValueChange={(val: any) => setPrintLang(val)}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="es">Español</SelectItem>
                                    <SelectItem value="en">Inglés</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>Formato</Label>
                            <RadioGroup
                                value={printMode}
                                onValueChange={(val: any) => setPrintMode(val)}
                            >
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="standard" id="r1" />
                                    <Label htmlFor="r1">
                                        Estándar (Recomendado)
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="detailed" id="r2" />
                                    <Label htmlFor="r2">
                                        Detallado (Incluye dimensiones/pesos por
                                        ítem)
                                    </Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="simple" id="r3" />
                                    <Label htmlFor="r3">
                                        Simple (Solo totales)
                                    </Label>
                                </div>
                            </RadioGroup>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setPrintModalOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button onClick={handlePrint}>
                            <Printer className="mr-2 h-4 w-4" />
                            Generar PDF
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

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
                                {quote.quote_number}
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
