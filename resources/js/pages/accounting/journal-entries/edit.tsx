import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import journalEntryRoutes from '@/routes/accounting/journal-entries';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    Plus,
    Save,
    Trash2,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    normal_balance: string;
    currency_code: string | null;
}

interface Currency {
    id: number;
    code: string;
    symbol: string;
    name: string;
}

interface EntryLine {
    id?: number;
    account_id: number;
    account?: { code: string; name: string };
    description: string | null;
    currency_code: string;
    exchange_rate: number;
    debit: number;
    credit: number;
}

interface JournalEntry {
    id: number;
    entry_number: string;
    date: string;
    description: string;
    status: string;
    lines: EntryLine[];
}

interface Props {
    entry: JournalEntry;
    accounts: Account[];
    currencies: Currency[];
}

interface Line {
    account_id: number | '';
    description: string;
    currency_code: string;
    exchange_rate: number;
    debit: number | '';
    credit: number | '';
}

interface PageProps {
    errors?: Record<string, string>;
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: unknown;
}

export default function JournalEntryEdit({
    entry,
    accounts,
    currencies,
}: Props) {
    const { errors = {}, flash } = usePage<PageProps>().props;
    const [isSubmitting, setIsSubmitting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Contabilidad', href: '/accounting' },
        { title: 'Libro Diario', href: journalEntryRoutes.index().url },
        {
            title: entry.entry_number,
            href: journalEntryRoutes.show(entry.id).url,
        },
        {
            title: 'Editar',
            href: journalEntryRoutes.edit(entry.id).url,
        },
    ];

    const [formData, setFormData] = useState({
        date: entry.date.split('T')[0],
        description: entry.description,
    });

    const [lines, setLines] = useState<Line[]>(
        entry.lines.map((line) => ({
            account_id: line.account_id,
            description: line.description || '',
            currency_code: line.currency_code,
            exchange_rate: line.exchange_rate,
            debit: line.debit > 0 ? line.debit : '',
            credit: line.credit > 0 ? line.credit : '',
        })),
    );

    // Calculate totals
    const totals = useMemo(() => {
        const totalDebit = lines.reduce((sum, line) => {
            const debit =
                typeof line.debit === 'number'
                    ? line.debit
                    : parseFloat(line.debit as string) || 0;
            const rate = line.exchange_rate || 1;
            return sum + debit * rate;
        }, 0);

        const totalCredit = lines.reduce((sum, line) => {
            const credit =
                typeof line.credit === 'number'
                    ? line.credit
                    : parseFloat(line.credit as string) || 0;
            const rate = line.exchange_rate || 1;
            return sum + credit * rate;
        }, 0);

        const difference = Math.abs(totalDebit - totalCredit);
        const isBalanced = difference < 0.0001;

        return { totalDebit, totalCredit, difference, isBalanced };
    }, [lines]);

    const addLine = useCallback(() => {
        const defaultCurrency = currencies[0]?.code || 'USD';
        setLines((prev) => [
            ...prev,
            {
                account_id: '',
                description: '',
                currency_code: defaultCurrency,
                exchange_rate: 1,
                debit: '',
                credit: '',
            },
        ]);
    }, [currencies]);

    const removeLine = useCallback((index: number) => {
        setLines((prev) => {
            if (prev.length <= 2) return prev;
            return prev.filter((_, i) => i !== index);
        });
    }, []);

    const updateLine = useCallback(
        (index: number, field: keyof Line, value: string | number) => {
            setLines((prev) => {
                const newLines = [...prev];

                if (field === 'debit' || field === 'credit') {
                    const numValue =
                        value === '' ? '' : parseFloat(value as string) || 0;
                    newLines[index] = { ...newLines[index], [field]: numValue };

                    if (numValue && numValue > 0) {
                        const oppositeField =
                            field === 'debit' ? 'credit' : 'debit';
                        newLines[index][oppositeField] = '';
                    }
                } else if (field === 'account_id') {
                    newLines[index] = {
                        ...newLines[index],
                        [field]: value === '' ? '' : parseInt(value as string),
                    };
                } else if (field === 'exchange_rate') {
                    newLines[index] = {
                        ...newLines[index],
                        [field]: parseFloat(value as string) || 1,
                    };
                } else {
                    newLines[index] = { ...newLines[index], [field]: value };
                }

                return newLines;
            });
        },
        [],
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const cleanedLines = lines.map((line) => ({
            ...line,
            account_id: line.account_id || 0,
            debit:
                typeof line.debit === 'number'
                    ? line.debit
                    : parseFloat(line.debit as string) || 0,
            credit:
                typeof line.credit === 'number'
                    ? line.credit
                    : parseFloat(line.credit as string) || 0,
        }));

        const submitData = {
            ...formData,
            lines: cleanedLines,
        };

        router.put(journalEntryRoutes.update(entry.id).url, submitData, {
            onFinish: () => setIsSubmitting(false),
        });
    };

    const formatNumber = (num: number) => {
        return new Intl.NumberFormat('es-DO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    // Check for line-level errors
    const lineErrors = Object.entries(errors)
        .filter(([key]) => key.startsWith('lines'))
        .map(([key, message]) => ({ key, message }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${entry.entry_number}`} />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={journalEntryRoutes.show(entry.id).url}>
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                    </Button>
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold tracking-tight text-white">
                                Editar {entry.entry_number}
                            </h1>
                            <Badge
                                variant="outline"
                                className="border-amber-500/50 bg-amber-500/10 text-amber-400"
                            >
                                Borrador
                            </Badge>
                        </div>
                        <p className="text-sm text-slate-400">
                            Modificar el asiento contable
                        </p>
                    </div>
                </div>

                {/* Flash error message */}
                {flash?.error && (
                    <Alert
                        variant="destructive"
                        className="border-red-500/50 bg-red-500/10"
                    >
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {/* General form errors */}
                {(errors.date || errors.description) && (
                    <Alert
                        variant="destructive"
                        className="border-red-500/50 bg-red-500/10"
                    >
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <ul className="list-inside list-disc space-y-1">
                                {errors.date && <li>{errors.date}</li>}
                                {errors.description && (
                                    <li>{errors.description}</li>
                                )}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Header Info */}
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardHeader>
                            <CardTitle className="text-lg text-white">
                                Información del Asiento
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="date"
                                        className="text-slate-300"
                                    >
                                        Fecha *
                                    </Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={formData.date}
                                        onChange={(e) =>
                                            setFormData((prev) => ({
                                                ...prev,
                                                date: e.target.value,
                                            }))
                                        }
                                        className={`border-white/10 bg-slate-900/50 ${errors.date ? 'border-red-500' : ''}`}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="description"
                                        className="text-slate-300"
                                    >
                                        Descripción *
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={formData.description}
                                        onChange={(e) =>
                                            setFormData((prev) => ({
                                                ...prev,
                                                description: e.target.value,
                                            }))
                                        }
                                        placeholder="Descripción del asiento contable..."
                                        className={`min-h-[80px] border-white/10 bg-slate-900/50 ${errors.description ? 'border-red-500' : ''}`}
                                        required
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Lines */}
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-lg text-white">
                                Líneas del Asiento
                            </CardTitle>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addLine}
                            >
                                <Plus className="mr-1 h-4 w-4" />
                                Agregar Línea
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {/* Show all line errors */}
                            {lineErrors.length > 0 && (
                                <Alert
                                    variant="destructive"
                                    className="mb-4 border-red-500/50 bg-red-500/10"
                                >
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <ul className="list-inside list-disc space-y-1">
                                            {lineErrors.map(
                                                ({ key, message }) => (
                                                    <li key={key}>{message}</li>
                                                ),
                                            )}
                                        </ul>
                                    </AlertDescription>
                                </Alert>
                            )}

                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="border-white/10 hover:bg-transparent">
                                            <TableHead className="w-[250px] text-slate-400">
                                                Cuenta *
                                            </TableHead>
                                            <TableHead className="text-slate-400">
                                                Descripción
                                            </TableHead>
                                            <TableHead className="w-[100px] text-slate-400">
                                                Moneda
                                            </TableHead>
                                            <TableHead className="w-[120px] text-right text-slate-400">
                                                Débito
                                            </TableHead>
                                            <TableHead className="w-[120px] text-right text-slate-400">
                                                Crédito
                                            </TableHead>
                                            <TableHead className="w-[50px]"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {lines.map((line, index) => (
                                            <TableRow
                                                key={index}
                                                className="border-white/10 hover:bg-slate-700/30"
                                            >
                                                <TableCell>
                                                    <Select
                                                        value={
                                                            line.account_id
                                                                ? line.account_id.toString()
                                                                : ''
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            updateLine(
                                                                index,
                                                                'account_id',
                                                                value,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger
                                                            className={`border-white/10 bg-slate-900/50 ${errors[`lines.${index}.account_id`] ? 'border-red-500' : ''}`}
                                                        >
                                                            <SelectValue placeholder="Seleccionar cuenta" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {accounts.map(
                                                                (account) => (
                                                                    <SelectItem
                                                                        key={
                                                                            account.id
                                                                        }
                                                                        value={account.id.toString()}
                                                                    >
                                                                        {
                                                                            account.code
                                                                        }{' '}
                                                                        -{' '}
                                                                        {
                                                                            account.name
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        placeholder="Descripción de la línea"
                                                        value={line.description}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                index,
                                                                'description',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className="border-white/10 bg-slate-900/50"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Select
                                                        value={
                                                            line.currency_code
                                                        }
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            updateLine(
                                                                index,
                                                                'currency_code',
                                                                value,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger className="border-white/10 bg-slate-900/50">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {currencies.map(
                                                                (currency) => (
                                                                    <SelectItem
                                                                        key={
                                                                            currency.id
                                                                        }
                                                                        value={
                                                                            currency.code
                                                                        }
                                                                    >
                                                                        {
                                                                            currency.code
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0.00"
                                                        value={line.debit}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                index,
                                                                'debit',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className={`text-right font-mono ${errors[`lines.${index}.debit`] ? 'border-red-500' : ''}`}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0.00"
                                                        value={line.credit}
                                                        onChange={(e) =>
                                                            updateLine(
                                                                index,
                                                                'credit',
                                                                e.target.value,
                                                            )
                                                        }
                                                        className={`text-right font-mono ${errors[`lines.${index}.credit`] ? 'border-red-500' : ''}`}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() =>
                                                            removeLine(index)
                                                        }
                                                        disabled={
                                                            lines.length <= 2
                                                        }
                                                        className="text-slate-400 hover:text-red-400"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Totals */}
                            <div className="mt-6 flex justify-end">
                                <div className="w-full max-w-sm space-y-2 rounded-lg border border-white/10 bg-slate-900/50 p-4">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-400">
                                            Total Débito:
                                        </span>
                                        <span className="font-mono text-white">
                                            {formatNumber(totals.totalDebit)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-slate-400">
                                            Total Crédito:
                                        </span>
                                        <span className="font-mono text-white">
                                            {formatNumber(totals.totalCredit)}
                                        </span>
                                    </div>
                                    <div className="border-t border-white/10 pt-2">
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">
                                                Diferencia:
                                            </span>
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className={`font-mono ${
                                                        totals.isBalanced
                                                            ? 'text-emerald-400'
                                                            : 'text-red-400'
                                                    }`}
                                                >
                                                    {formatNumber(
                                                        totals.difference,
                                                    )}
                                                </span>
                                                {totals.isBalanced ? (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-emerald-500/50 bg-emerald-500/10 text-emerald-400"
                                                    >
                                                        <CheckCircle2 className="mr-1 h-3 w-3" />
                                                        Balanceado
                                                    </Badge>
                                                ) : (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-red-500/50 bg-red-500/10 text-red-400"
                                                    >
                                                        <AlertCircle className="mr-1 h-3 w-3" />
                                                        Desbalanceado
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex justify-end gap-4">
                        <Button type="button" variant="outline" asChild>
                            <Link href={journalEntryRoutes.show(entry.id).url}>
                                Cancelar
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={isSubmitting}
                            className="bg-emerald-600 hover:bg-emerald-700"
                        >
                            <Save className="mr-2 h-4 w-4" />
                            {isSubmitting ? 'Guardando...' : 'Guardar Cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
