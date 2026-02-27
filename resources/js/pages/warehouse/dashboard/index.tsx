import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    Activity,
    ArrowDownRight,
    ArrowUpRight,
    BarChart3,
    ClipboardList,
    Package,
    Users,
    Warehouse,
} from 'lucide-react';
import React from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface Kpis {
    total_items: number;
    total_qty: number;
    total_skus: number;
    total_clients: number;
    total_receipts: number;
    received_receipts: number;
    total_movements: number;
    inbound_qty: number;
    outbound_qty: number;
    adjustments: number;
    period: { from: string; to: string };
}

interface MovementByDay {
    date: string;
    inbound: number;
    outbound: number;
    adjustments: number;
}

interface MovementByType {
    type: string;
    label: string;
    count: number;
    qty: number;
}

interface TopClient {
    customer_id: number;
    name: string;
    total_qty: number;
    sku_count: number;
}

interface ReceiptByStatus {
    status: string;
    label: string;
    color: string;
    count: number;
}

interface RecentReceipt {
    id: number;
    receipt_number: string;
    warehouse: string;
    customer: string;
    status: string;
    status_label: string;
    status_color: string;
    created_at: string;
}

interface RecentMovement {
    id: number;
    type: string;
    type_label: string;
    sku: string;
    qty: number;
    user: string;
    created_at: string;
}

interface Props {
    data: {
        kpis: Kpis;
        movementsByDay: MovementByDay[];
        movementsByType: MovementByType[];
        topClients: TopClient[];
        receiptsByStatus: ReceiptByStatus[];
        recentReceipts: RecentReceipt[];
        recentMovements: RecentMovement[];
    };
    filters: {
        warehouse_id?: string;
        date_from: string;
        date_to: string;
    };
    warehouses: { id: number; name: string; code: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Warehouse', href: '/warehouse-orders' },
    { title: 'Dashboard', href: '/warehouse/dashboard' },
];

// Colors for charts
const COLORS = [
    '#8b5cf6',
    '#06b6d4',
    '#f59e0b',
    '#10b981',
    '#ef4444',
    '#6366f1',
];

export default function WarehouseDashboard({
    data,
    filters,
    warehouses,
}: Props) {
    const {
        kpis,
        movementsByDay,
        movementsByType,
        topClients,
        receiptsByStatus,
        recentReceipts,
        recentMovements,
    } = data;

    // Debounce timer ref
    const debounceRef = React.useRef<NodeJS.Timeout | null>(null);

    const handleFilterChange = (key: string, value: string) => {
        // For date fields, debounce to avoid spamming server
        if (key === 'date_from' || key === 'date_to') {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
            debounceRef.current = setTimeout(() => {
                router.get(
                    '/warehouse/dashboard',
                    {
                        ...filters,
                        [key]: value === 'all' ? undefined : value,
                    },
                    { preserveState: true, preserveScroll: true },
                );
            }, 500);
        } else {
            router.get(
                '/warehouse/dashboard',
                {
                    ...filters,
                    [key]: value === 'all' ? undefined : value,
                },
                { preserveState: true, preserveScroll: true },
            );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Warehouse Dashboard" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 shadow-lg">
                            <BarChart3 className="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">
                                Warehouse Dashboard
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {kpis.period.from} — {kpis.period.to}
                            </p>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="flex items-center gap-3">
                        <div className="w-44">
                            <Select
                                value={filters.warehouse_id || 'all'}
                                onValueChange={(v) =>
                                    handleFilterChange('warehouse_id', v)
                                }
                            >
                                <SelectTrigger>
                                    <Warehouse className="mr-2 h-4 w-4" />
                                    <SelectValue placeholder="Almacén" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todos los almacenes
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
                        <div className="flex items-center gap-2">
                            <Input
                                type="date"
                                className="w-36"
                                value={filters.date_from}
                                onChange={(e) =>
                                    handleFilterChange(
                                        'date_from',
                                        e.target.value,
                                    )
                                }
                            />
                            <span className="text-muted-foreground">—</span>
                            <Input
                                type="date"
                                className="w-36"
                                value={filters.date_to}
                                onChange={(e) =>
                                    handleFilterChange(
                                        'date_to',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                </div>

                {/* KPIs Row */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className="relative overflow-hidden">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-8 -translate-y-4 rounded-full bg-violet-500/10" />
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Items en Stock
                                    </p>
                                    <p className="text-3xl font-bold">
                                        {kpis.total_items.toLocaleString()}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {kpis.total_skus} SKUs únicos
                                    </p>
                                </div>
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-violet-100 dark:bg-violet-900">
                                    <Package className="h-6 w-6 text-violet-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="relative overflow-hidden">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-8 -translate-y-4 rounded-full bg-emerald-500/10" />
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Entradas
                                    </p>
                                    <p className="text-3xl font-bold text-emerald-600">
                                        +{kpis.inbound_qty.toLocaleString()}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {kpis.total_receipts} recepciones
                                    </p>
                                </div>
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900">
                                    <ArrowDownRight className="h-6 w-6 text-emerald-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="relative overflow-hidden">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-8 -translate-y-4 rounded-full bg-orange-500/10" />
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Salidas
                                    </p>
                                    <p className="text-3xl font-bold text-orange-600">
                                        -{kpis.outbound_qty.toLocaleString()}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Picking en período
                                    </p>
                                </div>
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900">
                                    <ArrowUpRight className="h-6 w-6 text-orange-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="relative overflow-hidden">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-8 -translate-y-4 rounded-full bg-blue-500/10" />
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Clientes Activos
                                    </p>
                                    <p className="text-3xl font-bold">
                                        {kpis.total_clients}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Con inventario
                                    </p>
                                </div>
                                <div className="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                    <Users className="h-6 w-6 text-blue-600" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Row */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Movements Over Time */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Activity className="h-4 w-4" />
                                Movimientos por Día
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={250}>
                                <LineChart data={movementsByDay}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-muted"
                                    />
                                    <XAxis dataKey="date" className="text-xs" />
                                    <YAxis className="text-xs" />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'hsl(var(--card))',
                                            border: '1px solid hsl(var(--border))',
                                            borderRadius: '8px',
                                        }}
                                    />
                                    <Legend />
                                    <Line
                                        type="monotone"
                                        dataKey="inbound"
                                        name="Entradas"
                                        stroke="#10b981"
                                        strokeWidth={2}
                                        dot={{ fill: '#10b981' }}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="outbound"
                                        name="Salidas"
                                        stroke="#f59e0b"
                                        strokeWidth={2}
                                        dot={{ fill: '#f59e0b' }}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Top Clients */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Users className="h-4 w-4" />
                                Top Clientes por Stock
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ResponsiveContainer width="100%" height={250}>
                                <BarChart data={topClients} layout="vertical">
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        className="stroke-muted"
                                    />
                                    <XAxis type="number" className="text-xs" />
                                    <YAxis
                                        type="category"
                                        dataKey="name"
                                        className="text-xs"
                                        width={100}
                                        tick={{ fontSize: 11 }}
                                    />
                                    <Tooltip
                                        contentStyle={{
                                            backgroundColor: 'hsl(var(--card))',
                                            border: '1px solid hsl(var(--border))',
                                            borderRadius: '8px',
                                        }}
                                        formatter={(value) => [
                                            typeof value === 'number'
                                                ? value.toLocaleString()
                                                : '0',
                                            'Qty',
                                        ]}
                                    />
                                    <Bar
                                        dataKey="total_qty"
                                        name="Cantidad"
                                        radius={[0, 4, 4, 0]}
                                    >
                                        {topClients.map((client, index) => (
                                            <Cell
                                                key={client.customer_id}
                                                fill={
                                                    COLORS[
                                                        index % COLORS.length
                                                    ]
                                                }
                                            />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>
                </div>

                {/* Status Cards Row */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Receipts by Status */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ClipboardList className="h-4 w-4" />
                                Recepciones por Estado
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-around">
                                {receiptsByStatus.map((item) => (
                                    <div
                                        key={item.status}
                                        className="text-center"
                                    >
                                        <p className="text-3xl font-bold">
                                            {item.count}
                                        </p>
                                        <Badge
                                            variant="outline"
                                            className={cn(
                                                'mt-1',
                                                item.color === 'yellow' &&
                                                    'border-amber-500 bg-amber-50 text-amber-700',
                                                item.color === 'blue' &&
                                                    'border-blue-500 bg-blue-50 text-blue-700',
                                                item.color === 'green' &&
                                                    'border-emerald-500 bg-emerald-50 text-emerald-700',
                                                item.color === 'gray' &&
                                                    'border-gray-500 bg-gray-50 text-gray-700',
                                            )}
                                        >
                                            {item.label}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Movements by Type */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Activity className="h-4 w-4" />
                                Movimientos por Tipo
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-3 gap-4">
                                {movementsByType.map((item) => (
                                    <div
                                        key={item.type}
                                        className="rounded-lg border p-3 text-center"
                                    >
                                        <p className="text-2xl font-bold">
                                            {item.count}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {item.label}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Activity Tables */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Recent Receipts */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">
                                Recepciones Recientes
                            </CardTitle>
                            <Link href="/warehouse-receipts">
                                <Button variant="ghost" size="sm">
                                    Ver todas
                                </Button>
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Recepción</TableHead>
                                        <TableHead>Cliente</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Fecha</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentReceipts.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="text-center text-muted-foreground"
                                            >
                                                Sin recepciones recientes
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        recentReceipts.map((receipt) => (
                                            <TableRow key={receipt.id}>
                                                <TableCell className="font-medium">
                                                    <Link
                                                        href={`/warehouse-receipts/${receipt.id}`}
                                                        className="hover:underline"
                                                    >
                                                        {receipt.receipt_number}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {receipt.customer || '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant="outline"
                                                        className={cn(
                                                            'text-xs',
                                                            receipt.status_color ===
                                                                'yellow' &&
                                                                'border-amber-500 bg-amber-50 text-amber-700',
                                                            receipt.status_color ===
                                                                'blue' &&
                                                                'border-blue-500 bg-blue-50 text-blue-700',
                                                            receipt.status_color ===
                                                                'green' &&
                                                                'border-emerald-500 bg-emerald-50 text-emerald-700',
                                                            receipt.status_color ===
                                                                'gray' &&
                                                                'border-gray-500 bg-gray-50 text-gray-700',
                                                        )}
                                                    >
                                                        {receipt.status_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {receipt.created_at}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Recent Movements */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">
                                Movimientos Recientes
                            </CardTitle>
                            <Link href="/warehouse/reports/movements">
                                <Button variant="ghost" size="sm">
                                    Ver todos
                                </Button>
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Tipo</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead className="text-right">
                                            Qty
                                        </TableHead>
                                        <TableHead>Usuario</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentMovements.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="text-center text-muted-foreground"
                                            >
                                                Sin movimientos recientes
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        recentMovements.map((mov) => (
                                            <TableRow key={mov.id}>
                                                <TableCell>
                                                    <Badge
                                                        variant="secondary"
                                                        className="text-xs"
                                                    >
                                                        {mov.type_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="font-mono text-sm">
                                                    {mov.sku}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <span
                                                        className={cn(
                                                            'font-medium',
                                                            mov.qty >= 0
                                                                ? 'text-emerald-600'
                                                                : 'text-red-600',
                                                        )}
                                                    >
                                                        {mov.qty >= 0
                                                            ? '+'
                                                            : ''}
                                                        {mov.qty}
                                                    </span>
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
                </div>
            </div>
        </AppLayout>
    );
}
