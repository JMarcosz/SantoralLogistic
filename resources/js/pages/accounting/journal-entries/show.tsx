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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import {
    ArrowLeft,
    CheckCircle,
    Edit,
    RefreshCcw,
    Trash2,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    normal_balance: string;
}

interface Line {
    id: number;
    account_id: number;
    account: Account;
    description: string | null;
    currency_code: string;
    exchange_rate: number;
    debit: number;
    credit: number;
    base_debit: number;
    base_credit: number;
}

interface User {
    id: number;
    name: string;
}

interface JournalEntry {
    id: number;
    entry_number: string;
    date: string;
    description: string;
    status: 'draft' | 'posted' | 'reversed';
    source_type: string | null;
    source_id: number | null;
    lines: Line[];
    created_by: User | null;
    posted_by: User | null;
    posted_at: string | null;
    reversed_by: User | null;
    reversed_at: string | null;
    reversal_of?: { id: number; entry_number: string } | null;
    reversal_entry?: { id: number; entry_number: string }[];
    total_debit: number;
    total_credit: number;
    total_base_debit: number;
    total_base_credit: number;
    is_balanced: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    entry: JournalEntry;
    can: {
        update: boolean;
        delete: boolean;
        post: boolean;
        reverse: boolean;
    };
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/20 text-slate-300 border-slate-500/30',
    posted: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
    reversed: 'bg-red-500/20 text-red-300 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    posted: 'Contabilizado',
    reversed: 'Reversado',
};

export default function JournalEntryShow({ entry, can }: Props) {
    const [confirmDialog, setConfirmDialog] = useState<
        'post' | 'reverse' | 'delete' | null
    >(null);
    const [processing, setProcessing] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Contabilidad', href: '/accounting' },
        { title: 'Libro Diario', href: '/accounting/journal-entries' },
        {
            title: entry.entry_number,
            href: `/accounting/journal-entries/${entry.id}`,
        },
    ];

    const executeAction = () => {
        if (!confirmDialog) return;

        setProcessing(true);

        const routes: Record<string, string> = {
            post: `/accounting/journal-entries/${entry.id}/post`,
            reverse: `/accounting/journal-entries/${entry.id}/reverse`,
            delete: `/accounting/journal-entries/${entry.id}`,
        };

        if (confirmDialog === 'delete') {
            router.delete(routes[confirmDialog], {
                onFinish: () => {
                    setProcessing(false);
                    setConfirmDialog(null);
                },
            });
        } else {
            router.post(
                routes[confirmDialog],
                {},
                {
                    onFinish: () => {
                        setProcessing(false);
                        setConfirmDialog(null);
                    },
                },
            );
        }
    };

    const formatNumber = (num: number) => {
        return new Intl.NumberFormat('es-DO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    const formatDateTime = (dateStr: string | null) => {
        if (!dateStr) return '-';
        return format(new Date(dateStr), 'dd MMM yyyy HH:mm', { locale: es });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Asiento ${entry.entry_number}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-start justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="icon" asChild>
                            <Link href="/accounting/journal-entries">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold text-white">
                                    {entry.entry_number}
                                </h1>
                                <Badge
                                    variant="outline"
                                    className={statusColors[entry.status]}
                                >
                                    {statusLabels[entry.status]}
                                </Badge>
                            </div>
                            <p className="text-sm text-slate-400">
                                {format(
                                    new Date(entry.date),
                                    'EEEE, dd MMMM yyyy',
                                    { locale: es },
                                )}
                            </p>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex gap-2">
                        {can.update && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={`/accounting/journal-entries/${entry.id}/edit`}
                                >
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar
                                </Link>
                            </Button>
                        )}
                        {can.post && (
                            <Button onClick={() => setConfirmDialog('post')}>
                                <CheckCircle className="mr-2 h-4 w-4" />
                                Contabilizar
                            </Button>
                        )}
                        {can.reverse && (
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmDialog('reverse')}
                            >
                                <RefreshCcw className="mr-2 h-4 w-4" />
                                Reversar
                            </Button>
                        )}
                        {can.delete && (
                            <Button
                                variant="outline"
                                onClick={() => setConfirmDialog('delete')}
                            >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                            </Button>
                        )}
                    </div>
                </div>

                {/* Reversal Info */}
                {entry.reversal_of && (
                    <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4">
                        <p className="text-amber-300">
                            Este es un asiento de reversión del asiento{' '}
                            <Link
                                href={`/accounting/journal-entries/${entry.reversal_of.id}`}
                                className="font-mono underline hover:no-underline"
                            >
                                {entry.reversal_of.entry_number}
                            </Link>
                        </p>
                    </div>
                )}

                {entry.reversal_entry && entry.reversal_entry.length > 0 && (
                    <div className="rounded-lg border border-red-500/30 bg-red-500/10 p-4">
                        <p className="text-red-300">
                            Este asiento fue reversado por{' '}
                            <Link
                                href={`/accounting/journal-entries/${entry.reversal_entry[0].id}`}
                                className="font-mono underline hover:no-underline"
                            >
                                {entry.reversal_entry[0].entry_number}
                            </Link>
                        </p>
                    </div>
                )}

                {/* Description */}
                <Card className="border-white/10 bg-slate-800/50">
                    <CardHeader>
                        <CardTitle className="text-lg text-white">
                            Descripción
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-slate-300">{entry.description}</p>
                    </CardContent>
                </Card>

                {/* Lines */}
                <Card className="border-white/10 bg-slate-800/50">
                    <CardHeader>
                        <CardTitle className="text-lg text-white">
                            Líneas del Asiento
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow className="border-white/10 hover:bg-transparent">
                                    <TableHead className="text-slate-400">
                                        Cuenta
                                    </TableHead>
                                    <TableHead className="text-slate-400">
                                        Descripción
                                    </TableHead>
                                    <TableHead className="text-center text-slate-400">
                                        Moneda
                                    </TableHead>
                                    <TableHead className="text-right text-slate-400">
                                        Débito
                                    </TableHead>
                                    <TableHead className="text-right text-slate-400">
                                        Crédito
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {entry.lines.map((line) => (
                                    <TableRow
                                        key={line.id}
                                        className="border-white/10 hover:bg-white/5"
                                    >
                                        <TableCell>
                                            <span className="font-mono text-sky-400">
                                                {line.account.code}
                                            </span>
                                            <span className="ml-2 text-slate-300">
                                                {line.account.name}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-slate-400">
                                            {line.description || '-'}
                                        </TableCell>
                                        <TableCell className="text-center text-slate-400">
                                            {line.currency_code}
                                        </TableCell>
                                        <TableCell className="text-right font-mono text-white">
                                            {line.debit > 0
                                                ? formatNumber(line.debit)
                                                : '-'}
                                        </TableCell>
                                        <TableCell className="text-right font-mono text-white">
                                            {line.credit > 0
                                                ? formatNumber(line.credit)
                                                : '-'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {/* Totals Row */}
                                <TableRow className="border-white/10 bg-slate-900/50 font-semibold hover:bg-slate-900/50">
                                    <TableCell
                                        colSpan={3}
                                        className="text-right text-slate-400"
                                    >
                                        Totales (Moneda Base):
                                    </TableCell>
                                    <TableCell className="text-right font-mono text-white">
                                        {formatNumber(entry.total_base_debit)}
                                    </TableCell>
                                    <TableCell className="text-right font-mono text-white">
                                        {formatNumber(entry.total_base_credit)}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        {/* Balance Status */}
                        <div className="mt-4 flex justify-end">
                            {entry.is_balanced ? (
                                <Badge
                                    variant="outline"
                                    className="border-emerald-500/30 bg-emerald-500/20 text-emerald-300"
                                >
                                    <CheckCircle className="mr-1 h-3 w-3" />
                                    Asiento Balanceado
                                </Badge>
                            ) : (
                                <Badge
                                    variant="outline"
                                    className="border-red-500/30 bg-red-500/20 text-red-300"
                                >
                                    <XCircle className="mr-1 h-3 w-3" />
                                    Asiento No Balanceado
                                </Badge>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Audit Info */}
                <Card className="border-white/10 bg-slate-800/50">
                    <CardHeader>
                        <CardTitle className="text-lg text-white">
                            Información de Auditoría
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <p className="text-sm text-slate-400">
                                    Creado por
                                </p>
                                <p className="text-white">
                                    {entry.created_by?.name || '-'}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {formatDateTime(entry.created_at)}
                                </p>
                            </div>
                            {entry.posted_at && (
                                <div>
                                    <p className="text-sm text-slate-400">
                                        Contabilizado por
                                    </p>
                                    <p className="text-white">
                                        {entry.posted_by?.name || '-'}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {formatDateTime(entry.posted_at)}
                                    </p>
                                </div>
                            )}
                            {entry.reversed_at && (
                                <div>
                                    <p className="text-sm text-slate-400">
                                        Reversado por
                                    </p>
                                    <p className="text-white">
                                        {entry.reversed_by?.name || '-'}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {formatDateTime(entry.reversed_at)}
                                    </p>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Confirmation Dialogs */}
            <AlertDialog
                open={confirmDialog !== null}
                onOpenChange={() => setConfirmDialog(null)}
            >
                <AlertDialogContent className="border-white/10 bg-slate-900">
                    <AlertDialogHeader>
                        <AlertDialogTitle className="text-white">
                            {confirmDialog === 'post' &&
                                '¿Contabilizar este asiento?'}
                            {confirmDialog === 'reverse' &&
                                '¿Reversar este asiento?'}
                            {confirmDialog === 'delete' &&
                                '¿Eliminar este asiento?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription className="text-slate-400">
                            {confirmDialog === 'post' &&
                                'Una vez contabilizado, el asiento no podrá ser editado. Solo podrá ser reversado.'}
                            {confirmDialog === 'reverse' &&
                                'Se creará un nuevo asiento con los montos invertidos para anular este asiento.'}
                            {confirmDialog === 'delete' &&
                                'Esta acción no se puede deshacer. El asiento será eliminado permanentemente.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel className="border-white/10 bg-slate-800">
                            Cancelar
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={executeAction}
                            disabled={processing}
                            className={
                                confirmDialog === 'delete'
                                    ? 'bg-red-600 hover:bg-red-700'
                                    : ''
                            }
                        >
                            {processing ? 'Procesando...' : 'Confirmar'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
