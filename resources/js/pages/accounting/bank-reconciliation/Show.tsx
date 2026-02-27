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
import { ArrowLeft, Check, CheckCircle2, Plus, Upload, X } from 'lucide-react';

interface Account {
    id: number;
    code: string;
    name: string;
}

interface JournalEntry {
    id: number;
    entry_number: string;
    date: string;
    description: string;
}

interface JournalEntryLine {
    id: number;
    debit: number;
    credit: number;
    description: string;
    journal_entry?: JournalEntry;
}

interface BankStatementLine {
    id: number;
    transaction_date: string;
    description: string;
    reference: string | null;
    amount: number;
    is_reconciled: boolean;
    journal_entry_line_id: number | null;
    journal_entry_line?: JournalEntryLine;
}

interface BankStatement {
    id: number;
    account_id: number;
    statement_date: string;
    period_start: string;
    period_end: string;
    reference: string | null;
    opening_balance: number;
    closing_balance: number;
    calculated_balance: number;
    status: 'draft' | 'in_progress' | 'completed';
    account?: Account;
    lines: BankStatementLine[];
}

interface Props {
    statement: BankStatement;
    unreconciledGlLines: JournalEntryLine[];
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/20 text-slate-300 border-slate-500/30',
    in_progress: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    completed: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    in_progress: 'En Progreso',
    completed: 'Completado',
};

export default function BankReconciliationShow({
    statement,
    unreconciledGlLines,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Contabilidad', href: '/accounting' },
        {
            title: 'Conciliación Bancaria',
            href: '/accounting/bank-reconciliation',
        },
        {
            title: statement.reference || `Estado #${statement.id}`,
            href: `/accounting/bank-reconciliation/${statement.id}`,
        },
    ];

    const formatAmount = (amount: number) => {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: 'DOP',
        }).format(amount);
    };

    const reconciledCount = statement.lines.filter(
        (l) => l.is_reconciled,
    ).length;
    const totalLines = statement.lines.length;
    const reconciliationProgress =
        totalLines > 0 ? Math.round((reconciledCount / totalLines) * 100) : 0;

    const handleComplete = () => {
        if (confirm('¿Marcar este estado de cuenta como completado?')) {
            router.post(
                `/accounting/bank-reconciliation/${statement.id}/complete`,
            );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Conciliación - ${statement.reference || statement.id}`}
            />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/accounting/bank-reconciliation">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold text-white">
                                {statement.account?.name}
                            </h1>
                            <p className="text-sm text-slate-400">
                                {statement.reference ||
                                    `Estado de cuenta #${statement.id}`}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge
                            variant="outline"
                            className={statusColors[statement.status]}
                        >
                            {statusLabels[statement.status]}
                        </Badge>
                        {statement.status !== 'completed' &&
                            reconciliationProgress === 100 && (
                                <Button size="sm" onClick={handleComplete}>
                                    <CheckCircle2 className="mr-2 h-4 w-4" />
                                    Completar
                                </Button>
                            )}
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 sm:grid-cols-4">
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardContent className="pt-6">
                            <p className="text-sm text-slate-400">
                                Saldo Inicial
                            </p>
                            <p className="text-2xl font-bold text-white">
                                {formatAmount(statement.opening_balance)}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardContent className="pt-6">
                            <p className="text-sm text-slate-400">
                                Saldo Final
                            </p>
                            <p className="text-2xl font-bold text-white">
                                {formatAmount(statement.closing_balance)}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardContent className="pt-6">
                            <p className="text-sm text-slate-400">Período</p>
                            <p className="text-lg font-semibold text-white">
                                {format(
                                    new Date(statement.period_start),
                                    'dd/MM/yy',
                                    { locale: es },
                                )}
                                {' - '}
                                {format(
                                    new Date(statement.period_end),
                                    'dd/MM/yy',
                                    { locale: es },
                                )}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardContent className="pt-6">
                            <p className="text-sm text-slate-400">Progreso</p>
                            <p className="text-2xl font-bold text-white">
                                {reconciliationProgress}%
                            </p>
                            <p className="text-xs text-slate-400">
                                {reconciledCount} / {totalLines} líneas
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Statement Lines */}
                <Card className="border-white/10 bg-slate-800/50">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-white">
                            Líneas del Estado de Cuenta
                        </CardTitle>
                        <div className="flex gap-2">
                            <Button variant="outline" size="sm">
                                <Upload className="mr-2 h-4 w-4" />
                                Importar CSV
                            </Button>
                            <Button variant="outline" size="sm">
                                <Plus className="mr-2 h-4 w-4" />
                                Agregar Línea
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow className="border-white/10">
                                    <TableHead className="text-slate-400">
                                        Fecha
                                    </TableHead>
                                    <TableHead className="text-slate-400">
                                        Descripción
                                    </TableHead>
                                    <TableHead className="text-slate-400">
                                        Referencia
                                    </TableHead>
                                    <TableHead className="text-right text-slate-400">
                                        Monto
                                    </TableHead>
                                    <TableHead className="text-center text-slate-400">
                                        Estado
                                    </TableHead>
                                    <TableHead className="text-center text-slate-400">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {statement.lines.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="h-24 text-center text-slate-400"
                                        >
                                            No hay líneas en este estado de
                                            cuenta
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    statement.lines.map((line) => (
                                        <TableRow
                                            key={line.id}
                                            className="border-white/10"
                                        >
                                            <TableCell className="text-white">
                                                {format(
                                                    new Date(
                                                        line.transaction_date,
                                                    ),
                                                    'dd/MM/yyyy',
                                                )}
                                            </TableCell>
                                            <TableCell className="max-w-xs truncate text-slate-300">
                                                {line.description}
                                            </TableCell>
                                            <TableCell className="font-mono text-sky-400">
                                                {line.reference || '-'}
                                            </TableCell>
                                            <TableCell
                                                className={`text-right font-mono ${line.amount >= 0 ? 'text-emerald-400' : 'text-red-400'}`}
                                            >
                                                {formatAmount(line.amount)}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {line.is_reconciled ? (
                                                    <span className="inline-flex items-center gap-1 text-emerald-400">
                                                        <Check className="h-4 w-4" />
                                                        Conciliado
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 text-amber-400">
                                                        <X className="h-4 w-4" />
                                                        Pendiente
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                {!line.is_reconciled && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        Conciliar
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Unreconciled GL Lines */}
                {unreconciledGlLines.length > 0 && (
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardHeader>
                            <CardTitle className="text-white">
                                Movimientos GL Pendientes de Conciliación
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow className="border-white/10">
                                        <TableHead className="text-slate-400">
                                            Asiento
                                        </TableHead>
                                        <TableHead className="text-slate-400">
                                            Fecha
                                        </TableHead>
                                        <TableHead className="text-slate-400">
                                            Descripción
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
                                    {unreconciledGlLines.map((line) => (
                                        <TableRow
                                            key={line.id}
                                            className="border-white/10"
                                        >
                                            <TableCell className="font-mono text-sky-400">
                                                {
                                                    line.journal_entry
                                                        ?.entry_number
                                                }
                                            </TableCell>
                                            <TableCell className="text-white">
                                                {line.journal_entry?.date &&
                                                    format(
                                                        new Date(
                                                            line.journal_entry.date,
                                                        ),
                                                        'dd/MM/yyyy',
                                                    )}
                                            </TableCell>
                                            <TableCell className="max-w-xs truncate text-slate-300">
                                                {line.description ||
                                                    line.journal_entry
                                                        ?.description}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-white">
                                                {line.debit > 0
                                                    ? formatAmount(line.debit)
                                                    : '-'}
                                            </TableCell>
                                            <TableCell className="text-right font-mono text-white">
                                                {line.credit > 0
                                                    ? formatAmount(line.credit)
                                                    : '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
