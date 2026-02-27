import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { FileText, Plus, Search } from 'lucide-react';
import { useState } from 'react';

interface JournalEntry {
    id: number;
    entry_number: string;
    date: string;
    description: string;
    status: 'draft' | 'posted' | 'reversed';
    total_debit: number;
    total_credit: number;
    lines_count: number;
    source_type: string | null;
    created_by?: { id: number; name: string };
    posted_by?: { id: number; name: string };
}

interface Status {
    value: string;
    label: string;
}

interface Filters {
    status: string | null;
    date_from: string | null;
    date_to: string | null;
    source_type: string | null;
    search: string | null;
}

interface Props {
    entries: {
        data: JournalEntry[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
    statuses: Status[];
    filters: Filters;
    can: {
        create: boolean;
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Libro Diario', href: '/accounting/journal-entries' },
];

export default function JournalEntriesIndex({
    entries,
    statuses,
    filters,
    can,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleFilter = () => {
        router.get(
            '/accounting/journal-entries',
            {
                search: search || undefined,
                status: status || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
            },
            { preserveState: true },
        );
    };

    const handleClear = () => {
        setSearch('');
        setStatus('');
        setDateFrom('');
        setDateTo('');
        router.get('/accounting/journal-entries');
    };

    const formatAmount = (amount: number) => {
        return new Intl.NumberFormat('es-DO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Libro Diario" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-white">
                            Libro Diario
                        </h1>
                        <p className="text-sm text-slate-400">
                            Gestión de asientos contables
                        </p>
                    </div>
                    {can.create && (
                        <Button asChild>
                            <Link href="/accounting/journal-entries/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Asiento
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Filters */}
                <div className="rounded-xl border border-white/10 bg-slate-800/50 p-4">
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="min-w-[200px] flex-1">
                            <label className="mb-1.5 block text-sm text-slate-400">
                                Buscar
                            </label>
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    placeholder="Número o descripción..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' && handleFilter()
                                    }
                                />
                            </div>
                        </div>

                        <div className="w-40">
                            <label className="mb-1.5 block text-sm text-slate-400">
                                Estado
                            </label>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    {statuses.map((s) => (
                                        <SelectItem
                                            key={s.value}
                                            value={s.value}
                                        >
                                            {s.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="w-40">
                            <label className="mb-1.5 block text-sm text-slate-400">
                                Fecha desde
                            </label>
                            <Input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                            />
                        </div>

                        <div className="w-40">
                            <label className="mb-1.5 block text-sm text-slate-400">
                                Fecha hasta
                            </label>
                            <Input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                            />
                        </div>

                        <div className="flex gap-2">
                            <Button onClick={handleFilter}>Filtrar</Button>
                            <Button variant="outline" onClick={handleClear}>
                                Limpiar
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className="rounded-xl border border-white/10 bg-slate-800/50">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-white/10 hover:bg-white/5">
                                <TableHead className="text-slate-400">
                                    Número
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Fecha
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Descripción
                                </TableHead>
                                <TableHead className="text-slate-400">
                                    Líneas
                                </TableHead>
                                <TableHead className="text-right text-slate-400">
                                    Débito
                                </TableHead>
                                <TableHead className="text-right text-slate-400">
                                    Crédito
                                </TableHead>
                                <TableHead className="text-center text-slate-400">
                                    Estado
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entries.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-32 text-center text-slate-400"
                                    >
                                        <FileText className="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        No hay asientos contables
                                    </TableCell>
                                </TableRow>
                            ) : (
                                entries.data.map((entry) => (
                                    <TableRow
                                        key={entry.id}
                                        className="cursor-pointer border-white/10 hover:bg-white/5"
                                        onClick={() =>
                                            router.visit(
                                                `/accounting/journal-entries/${entry.id}`,
                                            )
                                        }
                                    >
                                        <TableCell className="font-mono text-sky-400">
                                            {entry.entry_number}
                                        </TableCell>
                                        <TableCell className="text-white">
                                            {format(
                                                new Date(entry.date),
                                                'dd MMM yyyy',
                                                {
                                                    locale: es,
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate text-slate-300">
                                            {entry.description}
                                        </TableCell>
                                        <TableCell className="text-center text-slate-400">
                                            {entry.lines_count}
                                        </TableCell>
                                        <TableCell className="text-right font-mono text-white">
                                            {formatAmount(entry.total_debit)}
                                        </TableCell>
                                        <TableCell className="text-right font-mono text-white">
                                            {formatAmount(entry.total_credit)}
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <Badge
                                                variant="outline"
                                                className={
                                                    statusColors[entry.status]
                                                }
                                            >
                                                {statusLabels[entry.status]}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>

                    {/* Pagination */}
                    {entries.last_page > 1 && (
                        <div className="flex items-center justify-center gap-2 border-t border-white/10 p-4">
                            {entries.links.map((link, i) => (
                                <Button
                                    key={i}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    size="sm"
                                    disabled={!link.url}
                                    onClick={() =>
                                        link.url && router.visit(link.url)
                                    }
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
