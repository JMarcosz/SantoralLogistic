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
import { formatDate } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Eye, Package, Plus } from 'lucide-react';

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface Customer {
    id: number;
    name: string;
}

interface Receipt {
    id: number;
    receipt_number: string | null;
    reference: string | null;
    status: string;
    expected_at: string | null;
    received_at: string | null;
    created_at: string;
    warehouse: Warehouse;
    customer: Customer;
}

interface Status {
    value: string;
    label: string;
}

interface Props {
    receipts: {
        data: Receipt[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: {
        warehouse_id?: string;
        customer_id?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
    warehouses: Warehouse[];
    customers: Customer[];
    statuses: Status[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Recepciones', href: '/warehouse-receipts' },
];

const STATUS_COLORS: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    received: 'bg-blue-100 text-blue-800',
    closed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

export default function WarehouseReceiptsIndex({
    receipts,
    filters,
    warehouses,
    customers,
    statuses,
}: Props) {
    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/warehouse-receipts',
            {
                ...filters,
                [key]: value === 'all' ? undefined : value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        router.get('/warehouse-receipts', {}, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recepciones" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Package className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">Recepciones</h1>
                            <p className="text-sm text-muted-foreground">
                                {receipts.total} recepciones de almacén
                            </p>
                        </div>
                    </div>
                    <Link href="/warehouse-receipts/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Recepción
                        </Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="w-48">
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
                                            Todos los almacenes
                                        </SelectItem>
                                        {warehouses.map((w) => (
                                            <SelectItem
                                                key={w.id}
                                                value={w.id.toString()}
                                            >
                                                {w.name} ({w.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="w-48">
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
                                            Todos los clientes
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
                            <div className="w-40">
                                <Label className="text-xs">Estado</Label>
                                <Select
                                    value={filters.status || 'all'}
                                    onValueChange={(v) =>
                                        handleFilterChange('status', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Todos
                                        </SelectItem>
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
                                <Label className="text-xs">Desde</Label>
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
                                <Label className="text-xs">Hasta</Label>
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
                            {Object.keys(filters).some(
                                (k) => filters[k as keyof typeof filters],
                            ) && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearFilters}
                                >
                                    Limpiar filtros
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
                                    <TableHead className="w-32">
                                        Número
                                    </TableHead>
                                    <TableHead>Almacén</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>Referencia</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Esperado</TableHead>
                                    <TableHead>Recibido</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {receipts.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={8}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No hay recepciones
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    receipts.data.map((receipt) => (
                                        <TableRow key={receipt.id}>
                                            <TableCell className="font-medium">
                                                {receipt.receipt_number ||
                                                    `#${receipt.id}`}
                                            </TableCell>
                                            <TableCell>
                                                {receipt.warehouse?.name || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {receipt.customer?.name || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {receipt.reference || '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    className={
                                                        STATUS_COLORS[
                                                            receipt.status
                                                        ] || ''
                                                    }
                                                >
                                                    {statuses.find(
                                                        (s) =>
                                                            s.value ===
                                                            receipt.status,
                                                    )?.label || receipt.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    receipt.expected_at,
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {formatDate(
                                                    receipt.received_at,
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Link
                                                    href={`/warehouse-receipts/${receipt.id}`}
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                    >
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {receipts.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {receipts.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
