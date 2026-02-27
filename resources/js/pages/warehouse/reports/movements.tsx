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
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    Calendar,
    Download,
    Filter,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';

interface Movement {
    id: number;
    date: string;
    type: string;
    type_label: string;
    warehouse: string;
    customer: string;
    sku: string;
    description: string;
    from_location: string | null;
    to_location: string | null;
    qty: number;
    reference: string;
    notes: string;
    user: string;
}

interface Kpis {
    period: { from: string; to: string };
    total_adjustments: number;
    total_adjustment_qty: number;
    cycle_count_adjustments: number;
    other_adjustments: number;
    cycle_counts_completed: number;
    cycle_count_lines_total: number;
    cycle_count_lines_with_diff: number;
    inventory_accuracy_rate: number;
}

interface Props {
    movements: {
        data: Movement[];
        links: { url: string | null; label: string; active: boolean }[];
        meta: { current_page: number; last_page: number; total: number };
    };
    filters: {
        date_from?: string;
        date_to?: string;
        warehouse_id?: string;
        customer_id?: string;
        sku?: string;
        type?: string;
    };
    kpis: Kpis;
    warehouses: { id: number; name: string; code: string }[];
    customers: { id: number; name: string }[];
    movementTypes: { value: string; label: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Warehouse', href: '/warehouse-orders' },
    { title: 'Kardex', href: '/warehouse/reports/movements' },
];

// Movement type colors
const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
        receive:
            'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300',
        putaway:
            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        pick: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        transfer:
            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        adjust: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
        return: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

export default function MovementsReport({
    movements,
    filters,
    kpis,
    warehouses,
    customers,
    movementTypes,
}: Props) {
    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/warehouse/reports/movements',
            {
                ...filters,
                [key]: value === 'all' ? undefined : value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (filters.date_from) params.append('date_from', filters.date_from);
        if (filters.date_to) params.append('date_to', filters.date_to);
        if (filters.warehouse_id)
            params.append('warehouse_id', filters.warehouse_id);
        if (filters.customer_id)
            params.append('customer_id', filters.customer_id);
        if (filters.sku) params.append('sku', filters.sku);
        if (filters.type) params.append('type', filters.type);

        window.open(
            `/warehouse/reports/movements/export?${params.toString()}`,
            '_blank',
        );
    };

    const clearFilters = () => {
        router.get('/warehouse/reports/movements', {}, { preserveState: true });
    };

    const hasFilters = Object.values(filters).some((v) => v);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kardex - Movimientos de Inventario" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600">
                            <Activity className="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">
                                Kardex Operativo
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {movements.meta?.total || movements.data.length}{' '}
                                movimientos en período
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" onClick={handleExport}>
                        <Download className="mr-2 h-4 w-4" />
                        Exportar Excel
                    </Button>
                </div>

                {/* KPIs */}
                <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <Card className="border-l-4 border-l-indigo-500">
                        <CardContent className="pt-4">
                            <p className="text-sm text-muted-foreground">
                                Movimientos
                            </p>
                            <p className="text-3xl font-bold">
                                {movements.meta?.total || movements.data.length}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-l-4 border-l-amber-500">
                        <CardContent className="pt-4">
                            <p className="text-sm text-muted-foreground">
                                Ajustes
                            </p>
                            <p className="text-3xl font-bold">
                                {kpis.total_adjustments}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Qty: {kpis.total_adjustment_qty}
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-l-4 border-l-emerald-500">
                        <CardContent className="pt-4">
                            <p className="text-sm text-muted-foreground">
                                Precisión
                            </p>
                            <p className="text-3xl font-bold">
                                {kpis.inventory_accuracy_rate}%
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="border-l-4 border-l-blue-500">
                        <CardContent className="pt-4">
                            <p className="text-sm text-muted-foreground">
                                Cycle Counts
                            </p>
                            <p className="text-3xl font-bold">
                                {kpis.cycle_counts_completed}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {kpis.cycle_count_lines_with_diff} con
                                diferencia
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Filter className="h-4 w-4" />
                            Filtros
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="w-40">
                                <Label className="flex items-center gap-1 text-xs">
                                    <Calendar className="h-3 w-3" />
                                    Desde
                                </Label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={filters.date_from || ''}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            'date_from',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="w-40">
                                <Label className="flex items-center gap-1 text-xs">
                                    <Calendar className="h-3 w-3" />
                                    Hasta
                                </Label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={filters.date_to || ''}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            'date_to',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="w-44">
                                <Label className="text-xs">Almacén</Label>
                                <Select
                                    value={filters.warehouse_id || 'all'}
                                    onValueChange={(v) =>
                                        handleFilterChange('warehouse_id', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {warehouses.map((w) => (
                                            <SelectItem
                                                key={w.id}
                                                value={w.id.toString()}
                                            >
                                                {w.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-44">
                                <Label className="text-xs">Cliente</Label>
                                <Select
                                    value={filters.customer_id || 'all'}
                                    onValueChange={(v) =>
                                        handleFilterChange('customer_id', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {customers.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={c.id.toString()}
                                            >
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-36">
                                <Label className="text-xs">Tipo</Label>
                                <Select
                                    value={filters.type || 'all'}
                                    onValueChange={(v) =>
                                        handleFilterChange('type', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
                                        {movementTypes.map((t) => (
                                            <SelectItem
                                                key={t.value}
                                                value={t.value}
                                            >
                                                {t.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-32">
                                <Label className="text-xs">SKU</Label>
                                <Input
                                    className="mt-1"
                                    placeholder="Buscar..."
                                    value={filters.sku || ''}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            'sku',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            {hasFilters && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearFilters}
                                >
                                    Limpiar
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-36">
                                        Fecha
                                    </TableHead>
                                    <TableHead className="w-28">Tipo</TableHead>
                                    <TableHead>Almacén</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>SKU</TableHead>
                                    <TableHead className="w-48">
                                        Ubicación
                                    </TableHead>
                                    <TableHead className="w-24 text-right">
                                        Qty
                                    </TableHead>
                                    <TableHead>Usuario</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {movements.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No hay movimientos en el período
                                            seleccionado
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    movements.data.map((mov) => (
                                        <TableRow key={mov.id}>
                                            <TableCell className="text-sm">
                                                {mov.date}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="secondary"
                                                    className={cn(
                                                        'text-xs font-medium',
                                                        getTypeColor(mov.type),
                                                    )}
                                                >
                                                    {mov.type_label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-sm">
                                                {mov.warehouse}
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {mov.customer || '-'}
                                            </TableCell>
                                            <TableCell className="font-mono text-sm font-medium">
                                                {mov.sku}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1 text-sm">
                                                    <span
                                                        className={cn(
                                                            'font-mono',
                                                            mov.from_location
                                                                ? ''
                                                                : 'text-muted-foreground',
                                                        )}
                                                    >
                                                        {mov.from_location ||
                                                            '—'}
                                                    </span>
                                                    <ArrowRight className="h-3 w-3 text-muted-foreground" />
                                                    <span
                                                        className={cn(
                                                            'font-mono',
                                                            mov.to_location
                                                                ? ''
                                                                : 'text-muted-foreground',
                                                        )}
                                                    >
                                                        {mov.to_location || '—'}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div
                                                    className={cn(
                                                        'flex items-center justify-end gap-1 font-medium',
                                                        mov.qty >= 0
                                                            ? 'text-emerald-600'
                                                            : 'text-red-600',
                                                    )}
                                                >
                                                    {mov.qty >= 0 ? (
                                                        <TrendingUp className="h-3 w-3" />
                                                    ) : (
                                                        <TrendingDown className="h-3 w-3" />
                                                    )}
                                                    {mov.qty >= 0 ? '+' : ''}
                                                    {mov.qty}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {mov.user || '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {movements.meta?.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Página {movements.meta.current_page} de{' '}
                            {movements.meta.last_page}
                        </p>
                        <div className="flex gap-2">
                            {movements.links
                                .filter(
                                    (link) =>
                                        !link.label.includes('Previous') &&
                                        !link.label.includes('Next'),
                                )
                                .slice(0, 10)
                                .map((link, i) => (
                                    <Button
                                        key={i}
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() =>
                                            link.url && router.get(link.url)
                                        }
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
