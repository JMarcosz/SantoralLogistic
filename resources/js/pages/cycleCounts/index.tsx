import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import cycleCountRoutes from '@/routes/cycle-counts';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Eye, Loader2, Plus } from 'lucide-react';
import { useState } from 'react';

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface CycleCount {
    id: number;
    warehouse: Warehouse;
    status: string;
    reference: string | null;
    scheduled_at: string | null;
    completed_at: string | null;
    created_by?: { name: string };
    created_at: string;
    lines_count: number;
}

interface PageProps {
    counts: {
        data: CycleCount[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        status?: string;
        warehouse_id?: string;
    };
    warehouses: Warehouse[];
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/20 text-slate-400 border-slate-500/30',
    in_progress: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
    completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
    cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    in_progress: 'En Progreso',
    completed: 'Completado',
    cancelled: 'Cancelado',
};

function formatDate(date: string | null): string {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-DO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function CycleCountsIndex({
    counts,
    filters,
    warehouses,
}: PageProps) {
    const [isLoading, setIsLoading] = useState(false);

    const handleFilter = (key: string, value: string) => {
        setIsLoading(true);
        const newFilters = {
            ...filters,
            [key]: value === 'all' ? undefined : value,
        };
        router.get(cycleCountRoutes.index().url, newFilters, {
            preserveState: true,
            onFinish: () => setIsLoading(false),
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Conteos Cíclicos',
                    href: cycleCountRoutes.index().url,
                },
            ]}
        >
            <Head title="Conteos Cíclicos" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            <ClipboardList className="h-6 w-6" />
                            Conteos Cíclicos
                        </h1>
                        <p className="text-muted-foreground">
                            Verificación y reconciliación de inventario físico
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={cycleCountRoutes.create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Conteo
                        </Link>
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-lg">Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-4">
                            <div className="w-48">
                                <label className="mb-1 block text-sm font-medium">
                                    Estado
                                </label>
                                <Select
                                    value={filters.status || 'all'}
                                    onValueChange={(v: string) =>
                                        handleFilter('status', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        <SelectItem value="draft">
                                            Borrador
                                        </SelectItem>
                                        <SelectItem value="in_progress">
                                            En Progreso
                                        </SelectItem>
                                        <SelectItem value="completed">
                                            Completado
                                        </SelectItem>
                                        <SelectItem value="cancelled">
                                            Cancelado
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-48">
                                <label className="mb-1 block text-sm font-medium">
                                    Almacén
                                </label>
                                <Select
                                    value={filters.warehouse_id || 'all'}
                                    onValueChange={(v: string) =>
                                        handleFilter('warehouse_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {warehouses.map((w) => (
                                            <SelectItem
                                                key={w.id}
                                                value={String(w.id)}
                                            >
                                                {w.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            {isLoading && (
                                <div className="flex items-end pb-2">
                                    <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardContent className="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Almacén</TableHead>
                                    <TableHead>Referencia</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-center">
                                        Líneas
                                    </TableHead>
                                    <TableHead>Programado</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {counts.data.map((count) => (
                                    <TableRow key={count.id}>
                                        <TableCell className="font-mono font-medium">
                                            #{count.id}
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium">
                                                {count.warehouse.name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {count.warehouse.code}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {count.reference || '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                className={
                                                    statusColors[count.status]
                                                }
                                            >
                                                {statusLabels[count.status] ||
                                                    count.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-center">
                                            <span className="font-mono">
                                                {count.lines_count}
                                            </span>
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(count.scheduled_at)}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatDate(count.created_at)}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={
                                                        cycleCountRoutes.show(
                                                            count.id,
                                                        ).url
                                                    }
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {counts.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-12 text-center text-muted-foreground"
                                        >
                                            No hay conteos cíclicos
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {counts.links && counts.links.length > 3 && (
                            <div className="mt-4 flex justify-center gap-1">
                                {counts.links.map((link, i) => (
                                    <Button
                                        key={i}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() =>
                                            link.url &&
                                            router.get(link.url, filters, {
                                                preserveState: true,
                                            })
                                        }
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
