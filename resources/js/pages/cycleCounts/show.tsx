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
import cycleCountRoutes from '@/routes/cycle-counts';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowLeft,
    Calendar,
    CheckCircle,
    ClipboardList,
    Hash,
    Loader2,
    Minus,
    Package,
    Play,
    Search,
    TrendingDown,
    TrendingUp,
    User,
    XCircle,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface Line {
    id: number;
    sku: string;
    description: string;
    location: string | null;
    expected_qty: number;
    counted_qty: number | null;
    difference_qty: number | null;
    is_counted: boolean;
    difference_type: 'none' | 'surplus' | 'shortage';
}

interface CycleCountData {
    id: number;
    warehouse: { id: number; name: string; code: string };
    status: string;
    status_label: string;
    status_color: string;
    reference: string | null;
    scheduled_at: string | null;
    completed_at: string | null;
    notes: string | null;
    created_by: string | null;
    created_at: string;
    lines: Line[];
    total_lines: number;
    counted_lines: number;
    counting_progress: number;
    lines_with_differences: number;
    can_start: boolean;
    can_complete: boolean;
    can_cancel: boolean;
}

interface PageProps {
    cycleCount: CycleCountData;
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: unknown;
}

const statusColors: Record<string, string> = {
    slate: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
    amber: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
    emerald: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    red: 'bg-red-500/20 text-red-400 border-red-500/30',
};

export default function CycleCountShow({ cycleCount }: PageProps) {
    const { flash } = usePage<PageProps>().props;
    const [editingLine, setEditingLine] = useState<Line | null>(null);
    const [countedQty, setCountedQty] = useState<string>('');
    const [cancelDialogOpen, setCancelDialogOpen] = useState(false);
    const [cancelReason, setCancelReason] = useState('');
    const [isProcessing, setIsProcessing] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [showFilter, setShowFilter] = useState<
        'all' | 'pending' | 'counted' | 'differences'
    >('all');
    const [notification, setNotification] = useState<{
        type: 'success' | 'error';
        message: string;
    } | null>(null);

    // Show flash messages
    useEffect(() => {
        if (flash?.success) {
            setNotification({ type: 'success', message: flash.success });
            setTimeout(() => setNotification(null), 4000);
        }
        if (flash?.error) {
            setNotification({ type: 'error', message: flash.error });
            setTimeout(() => setNotification(null), 5000);
        }
    }, [flash]);

    // Filter lines
    const filteredLines = useMemo(() => {
        let lines = cycleCount.lines;

        // Apply search
        if (searchTerm) {
            const term = searchTerm.toLowerCase();
            lines = lines.filter(
                (l) =>
                    l.sku.toLowerCase().includes(term) ||
                    l.description.toLowerCase().includes(term) ||
                    l.location?.toLowerCase().includes(term),
            );
        }

        // Apply filter
        switch (showFilter) {
            case 'pending':
                lines = lines.filter((l) => !l.is_counted);
                break;
            case 'counted':
                lines = lines.filter((l) => l.is_counted);
                break;
            case 'differences':
                lines = lines.filter((l) => l.difference_type !== 'none');
                break;
        }

        return lines;
    }, [cycleCount.lines, searchTerm, showFilter]);

    const handleStart = () => {
        setIsProcessing(true);
        router.post(
            cycleCountRoutes.start(cycleCount.id).url,
            {},
            {
                onFinish: () => setIsProcessing(false),
            },
        );
    };

    const handleComplete = () => {
        if (
            !confirm(
                '¿Está seguro de completar el conteo? Se generarán ajustes de inventario automáticamente.',
            )
        ) {
            return;
        }
        setIsProcessing(true);
        router.post(
            cycleCountRoutes.complete(cycleCount.id).url,
            {},
            {
                onFinish: () => setIsProcessing(false),
            },
        );
    };

    const handleCancel = () => {
        setIsProcessing(true);
        router.post(
            cycleCountRoutes.cancel(cycleCount.id).url,
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
        setCountedQty(
            line.counted_qty?.toString() || line.expected_qty.toString(),
        );
    };

    const handleLineUpdate = () => {
        if (!editingLine) return;
        const qty = parseFloat(countedQty);
        if (isNaN(qty) || qty < 0) {
            setNotification({ type: 'error', message: 'Cantidad inválida' });
            return;
        }
        setIsProcessing(true);
        router.patch(
            `/cycle-counts/${cycleCount.id}/lines/${editingLine.id}`,
            { counted_qty: qty },
            {
                onFinish: () => {
                    setIsProcessing(false);
                    setEditingLine(null);
                },
            },
        );
    };

    const isInProgress = cycleCount.status === 'in_progress';
    const isTerminal =
        cycleCount.status === 'completed' || cycleCount.status === 'cancelled';

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Conteos Cíclicos',
                    href: cycleCountRoutes.index().url,
                },
                {
                    title: `Conteo #${cycleCount.id}`,
                    href: cycleCountRoutes.show(cycleCount.id).url,
                },
            ]}
        >
            <Head title={`Conteo #${cycleCount.id}`} />

            {/* Notification Toast */}
            {notification && (
                <div
                    className={`fixed top-4 right-4 z-50 animate-in rounded-lg px-4 py-3 shadow-lg fade-in slide-in-from-top-2 ${
                        notification.type === 'success'
                            ? 'bg-emerald-500 text-white'
                            : 'bg-red-500 text-white'
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
                {/* Header with gradient */}
                <div className="rounded-xl bg-gradient-to-r from-primary/10 via-primary/5 to-transparent p-6">
                    <div className="flex items-start justify-between">
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" size="icon" asChild>
                                <Link href={cycleCountRoutes.index().url}>
                                    <ArrowLeft className="h-5 w-5" />
                                </Link>
                            </Button>
                            <div>
                                <h1 className="flex items-center gap-3 text-2xl font-bold">
                                    <ClipboardList className="h-6 w-6" />
                                    Conteo #{cycleCount.id}
                                    <Badge
                                        className={
                                            statusColors[
                                                cycleCount.status_color
                                            ]
                                        }
                                    >
                                        {cycleCount.status_label}
                                    </Badge>
                                </h1>
                                <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                                    <span className="flex items-center gap-1">
                                        <Package className="h-4 w-4" />
                                        {cycleCount.warehouse.name} (
                                        {cycleCount.warehouse.code})
                                    </span>
                                    {cycleCount.reference && (
                                        <span className="flex items-center gap-1">
                                            <Hash className="h-4 w-4" />
                                            {cycleCount.reference}
                                        </span>
                                    )}
                                    {cycleCount.created_by && (
                                        <span className="flex items-center gap-1">
                                            <User className="h-4 w-4" />
                                            {cycleCount.created_by}
                                        </span>
                                    )}
                                    <span className="flex items-center gap-1">
                                        <Calendar className="h-4 w-4" />
                                        {cycleCount.created_at}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex gap-2">
                            {cycleCount.can_start && (
                                <Button
                                    onClick={handleStart}
                                    disabled={isProcessing}
                                    size="lg"
                                >
                                    {isProcessing ? (
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <Play className="mr-2 h-4 w-4" />
                                    )}
                                    Iniciar Conteo
                                </Button>
                            )}
                            {cycleCount.can_complete && (
                                <Button
                                    onClick={handleComplete}
                                    disabled={isProcessing}
                                    size="lg"
                                    className="bg-emerald-600 hover:bg-emerald-700"
                                >
                                    {isProcessing ? (
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <CheckCircle className="mr-2 h-4 w-4" />
                                    )}
                                    Completar
                                </Button>
                            )}
                            {cycleCount.can_cancel && (
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
                </div>

                {/* Info Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card className="overflow-hidden">
                        <div className="h-1 bg-gradient-to-r from-blue-500 to-blue-600" />
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Progreso
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {cycleCount.counted_lines} /{' '}
                                {cycleCount.total_lines}
                            </div>
                            <div className="mt-2 h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-blue-500 transition-all duration-500"
                                    style={{
                                        width: `${cycleCount.counting_progress}%`,
                                    }}
                                />
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {cycleCount.counting_progress}% completado
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden">
                        <div
                            className={`h-1 ${cycleCount.lines_with_differences > 0 ? 'bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-gradient-to-r from-emerald-500 to-emerald-600'}`}
                        />
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Diferencias
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-2 text-2xl font-bold">
                                {cycleCount.lines_with_differences > 0 ? (
                                    <>
                                        <AlertTriangle className="h-5 w-5 text-amber-500" />
                                        <span className="text-amber-600">
                                            {cycleCount.lines_with_differences}
                                        </span>
                                    </>
                                ) : (
                                    <>
                                        <CheckCircle className="h-5 w-5 text-emerald-500" />
                                        <span className="text-emerald-600">
                                            0
                                        </span>
                                    </>
                                )}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Líneas con diferencias
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden">
                        <div className="h-1 bg-gradient-to-r from-purple-500 to-purple-600" />
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Pendientes
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">
                                {cycleCount.total_lines -
                                    cycleCount.counted_lines}
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Líneas sin contar
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden">
                        <div className="h-1 bg-gradient-to-r from-slate-500 to-slate-600" />
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                Estado
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Badge
                                className={`${statusColors[cycleCount.status_color]} text-base`}
                            >
                                {cycleCount.status_label}
                            </Badge>
                            {cycleCount.completed_at && (
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Completado: {cycleCount.completed_at}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Notes */}
                {cycleCount.notes && (
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm">Notas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm whitespace-pre-wrap">
                                {cycleCount.notes}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Lines Table */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle>Líneas de Conteo</CardTitle>
                                <CardDescription>
                                    {isInProgress
                                        ? 'Haga clic en una línea para registrar la cantidad contada'
                                        : cycleCount.status === 'draft'
                                          ? 'Inicie el conteo para comenzar a registrar'
                                          : 'Conteo finalizado'}
                                </CardDescription>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {/* Quick Filters */}
                                <div className="flex gap-1 rounded-lg bg-muted p-1">
                                    {[
                                        { key: 'all', label: 'Todos' },
                                        { key: 'pending', label: 'Pendientes' },
                                        { key: 'counted', label: 'Contados' },
                                        {
                                            key: 'differences',
                                            label: 'Diferencias',
                                        },
                                    ].map((f) => (
                                        <button
                                            key={f.key}
                                            onClick={() =>
                                                setShowFilter(
                                                    f.key as typeof showFilter,
                                                )
                                            }
                                            className={`rounded-md px-3 py-1.5 text-sm font-medium transition-all ${
                                                showFilter === f.key
                                                    ? 'bg-background shadow-sm'
                                                    : 'text-muted-foreground hover:text-foreground'
                                            }`}
                                        >
                                            {f.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                        {/* Search */}
                        <div className="relative mt-4">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Buscar por SKU, descripción o ubicación..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="pl-9"
                            />
                        </div>
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
                                            Esperado
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Contado
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Diferencia
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Estado
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredLines.map((line) => (
                                        <TableRow
                                            key={line.id}
                                            className={
                                                isInProgress
                                                    ? 'cursor-pointer transition-colors hover:bg-muted/50'
                                                    : ''
                                            }
                                            onClick={() =>
                                                isInProgress &&
                                                openLineEdit(line)
                                            }
                                        >
                                            <TableCell className="font-mono font-medium">
                                                {line.sku}
                                            </TableCell>
                                            <TableCell className="max-w-[200px] truncate">
                                                {line.description}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {line.location || '-'}
                                            </TableCell>
                                            <TableCell className="text-right font-mono">
                                                {line.expected_qty}
                                            </TableCell>
                                            <TableCell className="text-right font-mono">
                                                {line.counted_qty !== null
                                                    ? line.counted_qty
                                                    : '-'}
                                            </TableCell>
                                            <TableCell className="text-right font-mono">
                                                {line.difference_qty !==
                                                null ? (
                                                    <span
                                                        className={
                                                            line.difference_type ===
                                                            'surplus'
                                                                ? 'text-emerald-500'
                                                                : line.difference_type ===
                                                                    'shortage'
                                                                  ? 'text-red-500'
                                                                  : ''
                                                        }
                                                    >
                                                        {line.difference_type ===
                                                            'surplus' && '+'}
                                                        {line.difference_qty}
                                                    </span>
                                                ) : (
                                                    '-'
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {line.is_counted ? (
                                                    line.difference_type ===
                                                    'none' ? (
                                                        <CheckCircle className="inline h-4 w-4 text-emerald-500" />
                                                    ) : line.difference_type ===
                                                      'surplus' ? (
                                                        <TrendingUp className="inline h-4 w-4 text-amber-500" />
                                                    ) : (
                                                        <TrendingDown className="inline h-4 w-4 text-red-500" />
                                                    )
                                                ) : (
                                                    <Minus className="inline h-4 w-4 text-muted-foreground" />
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {filteredLines.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-12 text-center text-muted-foreground"
                                            >
                                                {searchTerm ||
                                                showFilter !== 'all'
                                                    ? 'No se encontraron líneas con los filtros aplicados'
                                                    : 'No hay líneas en este conteo'}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        {/* Results count */}
                        <p className="mt-3 text-sm text-muted-foreground">
                            Mostrando {filteredLines.length} de{' '}
                            {cycleCount.total_lines} líneas
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Edit Line Dialog */}
            <Dialog
                open={!!editingLine}
                onOpenChange={() => setEditingLine(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Registrar Conteo</DialogTitle>
                        <DialogDescription>
                            {editingLine?.sku} - {editingLine?.description}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="flex items-center justify-between rounded-lg bg-muted p-3">
                            <span className="text-sm text-muted-foreground">
                                Cantidad esperada:
                            </span>
                            <span className="font-mono text-lg font-bold">
                                {editingLine?.expected_qty}
                            </span>
                        </div>
                        <div className="space-y-2">
                            <label className="text-sm font-medium">
                                Cantidad contada
                            </label>
                            <Input
                                type="number"
                                step="0.0001"
                                min="0"
                                value={countedQty}
                                onChange={(e) => setCountedQty(e.target.value)}
                                className="text-center text-lg"
                                autoFocus
                            />
                        </div>
                        {countedQty && editingLine && (
                            <div className="rounded-lg border p-3">
                                <p className="text-sm text-muted-foreground">
                                    Diferencia:
                                </p>
                                <p
                                    className={`text-lg font-bold ${
                                        parseFloat(countedQty) >
                                        editingLine.expected_qty
                                            ? 'text-emerald-500'
                                            : parseFloat(countedQty) <
                                                editingLine.expected_qty
                                              ? 'text-red-500'
                                              : ''
                                    }`}
                                >
                                    {parseFloat(countedQty) >
                                    editingLine.expected_qty
                                        ? '+'
                                        : ''}
                                    {(
                                        parseFloat(countedQty) -
                                        editingLine.expected_qty
                                    ).toFixed(4)}
                                </p>
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setEditingLine(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleLineUpdate}
                            disabled={isProcessing}
                        >
                            {isProcessing ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : null}
                            Guardar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Cancel Dialog */}
            <Dialog open={cancelDialogOpen} onOpenChange={setCancelDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-red-600">
                            <XCircle className="h-5 w-5" />
                            Cancelar Conteo
                        </DialogTitle>
                        <DialogDescription>
                            ¿Está seguro de cancelar este conteo? Esta acción no
                            se puede deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2 py-4">
                        <label className="text-sm font-medium">
                            Razón (opcional)
                        </label>
                        <Textarea
                            value={cancelReason}
                            onChange={(e) => setCancelReason(e.target.value)}
                            placeholder="Indique la razón de la cancelación..."
                            rows={3}
                        />
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCancelDialogOpen(false)}
                        >
                            Volver
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleCancel}
                            disabled={isProcessing}
                        >
                            {isProcessing ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : null}
                            Confirmar Cancelación
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
