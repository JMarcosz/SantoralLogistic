import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Download, Search, X } from 'lucide-react';
import { useState } from 'react';

interface InventoryItem {
    id: number;
    warehouse: string;
    warehouse_code: string;
    customer: string;
    location: string;
    sku: string;
    item_code: string;
    description: string;
    qty: number;
    available_qty: number;
    reserved_qty: number;
    uom: string;
    lot_number: string | null;
    serial_number: string | null;
    expiration_date: string | null;
}

interface Props {
    inventory: {
        data: InventoryItem[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
        per_page: number;
    };
    filters: {
        warehouse_id?: string;
        customer_id?: string;
        item_code?: string;
        location_id?: string;
    };
    warehouses: { id: number; name: string; code: string }[];
    customers: { id: number; name: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Warehouse', href: '/warehouse-orders' },
    { title: 'Reportes', href: '/warehouse/reports/inventory' },
    { title: 'Inventario', href: '/warehouse/reports/inventory' },
];

export default function InventoryReport({
    inventory,
    filters,
    warehouses,
    customers,
}: Props) {
    const [values, setValues] = useState({
        warehouse_id: filters.warehouse_id || '',
        customer_id: filters.customer_id || '',
        item_code: filters.item_code || '',
        location_id: filters.location_id || '',
    });

    const handleSearch = () => {
        router.get(
            '/warehouse/reports/inventory',
            {
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                ...(values as any),
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleReset = () => {
        setValues({
            warehouse_id: '',
            customer_id: '',
            item_code: '',
            location_id: '',
        });
        router.get('/warehouse/reports/inventory');
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (values.warehouse_id)
            params.append('warehouse_id', values.warehouse_id);
        if (values.customer_id)
            params.append('customer_id', values.customer_id);
        if (values.item_code) params.append('item_code', values.item_code);
        if (values.location_id)
            params.append('location_id', values.location_id);

        window.open(
            `/warehouse/reports/inventory/export?${params.toString()}`,
            '_blank',
        );
    };

    const columns: ColumnDef<InventoryItem>[] = [
        {
            accessorKey: 'warehouse',
            header: ({ column }) => {
                return (
                    <Button
                        variant="ghost"
                        onClick={() =>
                            column.toggleSorting(column.getIsSorted() === 'asc')
                        }
                    >
                        Almacén
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                );
            },
        },
        {
            accessorKey: 'customer',
            header: ({ column }) => {
                return (
                    <Button
                        variant="ghost"
                        onClick={() =>
                            column.toggleSorting(column.getIsSorted() === 'asc')
                        }
                    >
                        Cliente
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                );
            },
            cell: ({ row }) => (
                <div
                    className="max-w-[150px] truncate"
                    title={row.getValue('customer')}
                >
                    {row.getValue('customer')}
                </div>
            ),
        },
        {
            accessorKey: 'location',
            header: 'Ubicación',
            cell: ({ row }) => (
                <span className="font-mono">{row.getValue('location')}</span>
            ),
        },
        {
            accessorKey: 'item_code',
            header: 'Item Code',
            cell: ({ row }) => (
                <span className="font-mono text-xs">
                    {row.getValue('item_code') || row.original.sku}
                </span>
            ),
        },
        {
            accessorKey: 'description',
            header: 'Descripción',
            cell: ({ row }) => (
                <div
                    className="max-w-[200px] truncate"
                    title={row.getValue('description')}
                >
                    {row.getValue('description')}
                </div>
            ),
        },
        {
            accessorKey: 'qty',
            header: () => <div className="text-right">Total</div>,
            cell: ({ row }) => (
                <div className="text-right font-medium">
                    {row.getValue('qty')}
                </div>
            ),
        },
        {
            accessorKey: 'available_qty',
            header: () => <div className="text-right">Disponible</div>,
            cell: ({ row }) => (
                <div className="text-right font-bold text-emerald-600">
                    {row.getValue('available_qty')}
                </div>
            ),
        },
        {
            accessorKey: 'uom',
            header: 'UOM',
            cell: ({ row }) => (
                <span className="text-xs text-muted-foreground">
                    {row.getValue('uom')}
                </span>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reporte de Inventario" />
            <div className="p-6">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">
                        Reporte de Inventario
                    </h1>
                    <Button variant="outline" onClick={handleExport}>
                        <Download className="mr-2 h-4 w-4" />
                        Exportar Excel
                    </Button>
                </div>

                {/* Filters */}
                <Card className="mb-6 shadow-sm">
                    <CardContent className="grid gap-4 p-4 md:grid-cols-2 lg:grid-cols-5">
                        <div className="space-y-2">
                            <Label>Almacén</Label>
                            <Select
                                value={values.warehouse_id}
                                onValueChange={(v) =>
                                    setValues({ ...values, warehouse_id: v })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
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
                        <div className="space-y-2">
                            <Label>Cliente</Label>
                            <Select
                                value={values.customer_id}
                                onValueChange={(v) =>
                                    setValues({ ...values, customer_id: v })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
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
                        <div className="space-y-2">
                            <Label>Item Code</Label>
                            <Input
                                placeholder="Buscar código..."
                                value={values.item_code}
                                onChange={(e) =>
                                    setValues({
                                        ...values,
                                        item_code: e.target.value,
                                    })
                                }
                                onKeyDown={(e) =>
                                    e.key === 'Enter' && handleSearch()
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Ubicación</Label>
                            <Input
                                placeholder="Código ubicación..."
                                value={values.location_id}
                                onChange={(e) =>
                                    setValues({
                                        ...values,
                                        location_id: e.target.value,
                                    })
                                }
                                onKeyDown={(e) =>
                                    e.key === 'Enter' && handleSearch()
                                }
                            />
                        </div>
                        <div className="flex items-end gap-2">
                            <Button className="flex-1" onClick={handleSearch}>
                                <Search className="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={handleReset}
                                title="Limpiar filtros"
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Data Table with custom pagination handling */}
                <DataTable
                    columns={columns}
                    data={inventory.data}
                    hidePagination={true}
                />

                {/* Server-side Pagination */}
                <div className="mt-4 flex flex-col items-center justify-between gap-4 py-4 md:flex-row">
                    <div className="text-sm text-muted-foreground">
                        Mostrando {inventory.from || 0} a {inventory.to || 0} de{' '}
                        {inventory.total} resultados
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {inventory.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                asChild={!!link.url}
                                disabled={!link.url}
                                className={
                                    !link.url
                                        ? 'pointer-events-none opacity-50'
                                        : ''
                                }
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        preserveState
                                        preserveScroll
                                        only={['inventory']}
                                    >
                                        <span
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    </Link>
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
