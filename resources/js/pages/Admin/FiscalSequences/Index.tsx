/* eslint-disable react-hooks/exhaustive-deps */
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { can } from '@/utils/permissions';
import { Head, Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { AlertCircle, Edit, FileText, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';

interface FiscalSequence {
    id: number;
    ncf_type: string;
    series?: string | null;
    ncf_from: string;
    ncf_to: string;
    current_ncf?: string | null;
    valid_from: string;
    valid_to: string;
    is_active: boolean;
    usage_percent?: number | null;
    is_exhausted: boolean;
    is_valid_now: boolean;
    near_exhaustion: boolean;
    near_expiration: boolean;
    days_until_expiration: number;
}

interface Props {
    sequences: {
        data: FiscalSequence[];
        current_page: number;
        last_page: number;
        total: number;
        links: unknown[];
    };
    ncfTypes: Record<string, string>;
    filters: {
        ncf_type?: string;
        series?: string;
        is_active?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administración',
        href: '#',
    },
    {
        title: 'Rangos NCF',
        href: '/admin/fiscal-sequences',
    },
];

export default function FiscalSequencesIndex({
    sequences,
    ncfTypes,
    filters,
}: Props) {
    const [ncfTypeFilter, setNcfTypeFilter] = useState(
        filters.ncf_type || 'all',
    );
    const [seriesFilter, setSeriesFilter] = useState(filters.series || '');
    const [isActiveFilter, setIsActiveFilter] = useState(
        filters.is_active || 'all',
    );

    // Debounce series filter
    const [debouncedSeries, setDebouncedSeries] = useState(seriesFilter);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedSeries(seriesFilter);
        }, 500);
        return () => clearTimeout(handler);
    }, [seriesFilter]);

    // Trigger router on filter change
    useEffect(() => {
        if (
            (ncfTypeFilter === 'all' ? '' : ncfTypeFilter) ===
                (filters.ncf_type || '') &&
            debouncedSeries === (filters.series || '') &&
            (isActiveFilter === 'all' ? '' : isActiveFilter) ===
                (filters.is_active || '')
        ) {
            return;
        }

        router.get(
            '/admin/fiscal-sequences',
            {
                ncf_type: ncfTypeFilter === 'all' ? undefined : ncfTypeFilter,
                series: debouncedSeries || undefined,
                is_active:
                    isActiveFilter === 'all' ? undefined : isActiveFilter,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [ncfTypeFilter, debouncedSeries, isActiveFilter]);

    const columns: ColumnDef<FiscalSequence>[] = [
        {
            accessorKey: 'ncf_type',
            header: 'Tipo NCF',
            cell: ({ row }) => {
                const type = row.getValue('ncf_type') as string;
                return (
                    <div className="space-y-1">
                        <Badge
                            variant="outline"
                            className="font-mono font-semibold"
                        >
                            {type}
                        </Badge>
                        <div className="text-xs text-muted-foreground">
                            {ncfTypes[type] || type}
                        </div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'series',
            header: 'Serie',
            cell: ({ row }) => (
                <span className="font-mono text-sm">
                    {row.getValue('series') || '—'}
                </span>
            ),
        },
        {
            accessorKey: 'ncf_from',
            header: 'Rango',
            cell: ({ row }) => {
                const from = row.getValue('ncf_from') as string;
                const to = row.original.ncf_to;
                return (
                    <div className="font-mono text-xs">
                        <div>{from}</div>
                        <div className="text-muted-foreground">→ {to}</div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'current_ncf',
            header: 'Actual',
            cell: ({ row }) => {
                const current = row.getValue('current_ncf') as string | null;
                return (
                    <span className="font-mono text-xs text-muted-foreground">
                        {current || 'Sin usar'}
                    </span>
                );
            },
        },
        {
            accessorKey: 'valid_from',
            header: 'Vigencia',
            cell: ({ row }) => {
                const from = row.getValue('valid_from') as string;
                const to = row.original.valid_to;
                const isValidNow = row.original.is_valid_now;
                return (
                    <div className="text-xs">
                        <div
                            className={
                                isValidNow
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            }
                        >
                            {from}
                        </div>
                        <div className="text-muted-foreground">→ {to}</div>
                    </div>
                );
            },
        },
        {
            accessorKey: 'is_active',
            header: 'Estado',
            cell: ({ row }) => {
                const isActive = row.getValue('is_active') as boolean;
                const isExhausted = row.original.is_exhausted;
                const nearExhaustion = row.original.near_exhaustion;
                const nearExpiration = row.original.near_expiration;
                const daysLeft = row.original.days_until_expiration;

                return (
                    <div className="space-y-1">
                        {isExhausted ? (
                            <Badge variant="destructive">Agotado</Badge>
                        ) : (
                            <Badge variant={isActive ? 'default' : 'secondary'}>
                                {isActive ? 'Activo' : 'Inactivo'}
                            </Badge>
                        )}

                        {nearExhaustion && !isExhausted && (
                            <Badge
                                variant="destructive"
                                className="bg-orange-500 hover:bg-orange-600"
                            >
                                ⚠️ Por agotarse
                            </Badge>
                        )}

                        {nearExpiration && (
                            <Badge
                                variant="outline"
                                className="border-orange-500 text-orange-600 dark:text-orange-400"
                            >
                                📅 {daysLeft}d restantes
                            </Badge>
                        )}
                    </div>
                );
            },
        },
        {
            accessorKey: 'usage_percent',
            header: 'Uso',
            cell: ({ row }) => {
                const percent = row.getValue('usage_percent') as number | null;
                const nearExhaustion = row.original.near_exhaustion;

                if (percent === null || percent === undefined)
                    return <span className="text-muted-foreground">—</span>;

                const getColor = (p: number) => {
                    if (p >= 90) return 'bg-red-500';
                    if (p >= 80) return 'bg-orange-500';
                    if (p >= 70) return 'bg-yellow-500';
                    if (p >= 50) return 'bg-blue-500';
                    return 'bg-emerald-500';
                };

                return (
                    <div className="w-28 space-y-1">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-xs font-medium">
                                {percent.toFixed(1)}%
                            </span>
                            {nearExhaustion && (
                                <AlertCircle className="h-3 w-3 text-orange-500" />
                            )}
                        </div>
                        <Progress
                            value={percent}
                            className="h-2"
                            indicatorClassName={getColor(percent)}
                        />
                    </div>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const sequence = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        {can('fiscal_sequences.manage') && (
                            <Link
                                href={`/admin/fiscal-sequences/${sequence.id}/edit`}
                            >
                                <Button variant="ghost" size="icon">
                                    <Edit className="h-4 w-4" />
                                </Button>
                            </Link>
                        )}
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rangos NCF" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-start gap-6">
                            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <FileText className="h-10 w-10 text-primary-foreground" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-4xl font-bold tracking-tight">
                                    Rangos NCF
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Administra los rangos de comprobantes
                                    fiscales (NCF)
                                </p>
                            </div>
                        </div>

                        {can('fiscal_sequences.manage') && (
                            <Link href="/admin/fiscal-sequences/create">
                                <Button size="lg" className="shadow-lg">
                                    <Plus className="mr-2 h-5 w-5" />
                                    Nuevo Rango
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Alert Banner */}
                {sequences.data.some(
                    (s) => s.near_exhaustion || s.near_expiration,
                ) && (
                    <Alert
                        variant="destructive"
                        className="border-orange-500 bg-orange-50 dark:bg-orange-950"
                    >
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>Atención: Rangos NCF Críticos</AlertTitle>
                        <AlertDescription>
                            Hay rangos NCF próximos a agotarse o expirar.
                            Revisar configuración DGII.
                        </AlertDescription>
                    </Alert>
                )}

                {/* Filters */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <Select
                        value={ncfTypeFilter}
                        onValueChange={setNcfTypeFilter}
                    >
                        <SelectTrigger className="w-[200px]">
                            <SelectValue placeholder="Tipo NCF" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los tipos</SelectItem>
                            {Object.entries(ncfTypes).map(([code, label]) => (
                                <SelectItem key={code} value={code}>
                                    {code} - {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Input
                        placeholder="Filtrar por serie..."
                        value={seriesFilter}
                        onChange={(e) => setSeriesFilter(e.target.value)}
                        className="w-[200px]"
                    />

                    <Select
                        value={isActiveFilter}
                        onValueChange={setIsActiveFilter}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos</SelectItem>
                            <SelectItem value="1">Activos</SelectItem>
                            <SelectItem value="0">Inactivos</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <DataTable columns={columns} data={sequences.data || []} />
            </div>
        </AppLayout>
    );
}
