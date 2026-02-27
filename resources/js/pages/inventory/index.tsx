import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { type BreadcrumbItem } from '@/types';
import { can } from '@/utils/permissions';
import { Head, router } from '@inertiajs/react';
import {
    ArrowRightLeft,
    Blocks,
    Download,
    History,
    Layers,
    List,
    MapPin,
    MoreHorizontal,
    PencilLine,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { AdjustDialog } from './components/adjust-dialog';
import { MovementsDialog } from './components/movements-dialog';
import { PutawayDialog } from './components/putaway-dialog';
import { RelocateDialog } from './components/relocate-dialog';

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface Customer {
    id: number;
    name: string;
}

interface Location {
    id: number;
    code: string;
    zone: string | null;
}

interface InventoryItem {
    id: number;
    sku: string;
    description: string | null;
    qty: number;
    available_qty?: number;
    uom: string;
    lot_number: string | null;
    serial_number: string | null;
    expiration_date: string | null;
    warehouse: Warehouse;
    customer: Customer;
    location: Location | null;
}

interface SummaryItem {
    warehouse_id: number;
    customer_id: number;
    sku: string;
    description: string | null;
    uom: string;
    total_qty: number;
    item_count: number;
    warehouse: Warehouse;
    customer: Customer;
}

interface Props {
    items: {
        data: (InventoryItem | SummaryItem)[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
        last_page: number;
    };
    filters: {
        warehouse_id?: string;
        customer_id?: string;
        sku?: string;
        location_code?: string;
        view?: string;
    };
    view: 'detail' | 'summary';
    warehouses: Warehouse[];
    customers: Customer[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Inventario', href: '/inventory' },
];

export default function InventoryIndex({
    items,
    filters,
    view,
    warehouses,
    customers,
}: Props) {
    // Dialog states
    const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(
        null,
    );
    const [putawayOpen, setPutawayOpen] = useState(false);
    const [relocateOpen, setRelocateOpen] = useState(false);
    const [adjustOpen, setAdjustOpen] = useState(false);
    const [movementsOpen, setMovementsOpen] = useState(false);

    // Local state for text inputs (with debounce)
    const [skuInput, setSkuInput] = useState(filters.sku || '');
    const [locationInput, setLocationInput] = useState(
        filters.location_code || '',
    );

    // Debounced filter update for SKU
    useEffect(() => {
        // Skip if value matches current filter (initial render or after navigation)
        if (skuInput === (filters.sku || '')) return;

        const timer = setTimeout(() => {
            router.get(
                '/inventory',
                { ...filters, sku: skuInput || undefined },
                { preserveState: true, preserveScroll: true },
            );
        }, 500);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [skuInput]);

    // Debounced filter update for Location
    useEffect(() => {
        // Skip if value matches current filter (initial render or after navigation)
        if (locationInput === (filters.location_code || '')) return;

        const timer = setTimeout(() => {
            router.get(
                '/inventory',
                { ...filters, location_code: locationInput || undefined },
                { preserveState: true, preserveScroll: true },
            );
        }, 500);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [locationInput]);

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/inventory',
            {
                ...filters,
                [key]: value === 'all' ? undefined : value,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleViewChange = (newView: 'detail' | 'summary') => {
        router.get(
            '/inventory',
            {
                ...filters,
                view: newView,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const clearFilters = () => {
        router.get('/inventory', { view }, { preserveState: true });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (filters.warehouse_id)
            params.append('warehouse_id', filters.warehouse_id);
        if (filters.customer_id)
            params.append('customer_id', filters.customer_id);
        if (filters.sku) params.append('sku', filters.sku);
        window.location.href = `/inventory/export?${params.toString()}`;
    };

    const openPutaway = (item: InventoryItem) => {
        setSelectedItem(item);
        setPutawayOpen(true);
    };

    const openRelocate = (item: InventoryItem) => {
        setSelectedItem(item);
        setRelocateOpen(true);
    };

    const openAdjust = (item: InventoryItem) => {
        setSelectedItem(item);
        setAdjustOpen(true);
    };

    const openMovements = (item: InventoryItem) => {
        setSelectedItem(item);
        setMovementsOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventario" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <Blocks className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">Inventario</h1>
                            <p className="text-sm text-muted-foreground">
                                {items.total}{' '}
                                {view === 'summary' ? 'SKUs' : 'items'} en
                                inventario
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* View Toggle */}
                        <div className="flex rounded-lg border p-1">
                            <Button
                                variant={
                                    view === 'detail' ? 'default' : 'ghost'
                                }
                                size="sm"
                                onClick={() => handleViewChange('detail')}
                                className="gap-1.5"
                            >
                                <List className="h-4 w-4" />
                                Detalle
                            </Button>
                            <Button
                                variant={
                                    view === 'summary' ? 'default' : 'ghost'
                                }
                                size="sm"
                                onClick={() => handleViewChange('summary')}
                                className="gap-1.5"
                            >
                                <Layers className="h-4 w-4" />
                                Por SKU
                            </Button>
                        </div>
                        {can('inventory.view') && (
                            <Button variant="outline" onClick={handleExport}>
                                <Download className="mr-2 h-4 w-4" />
                                Exportar
                            </Button>
                        )}
                    </div>
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
                            <div className="w-40">
                                <Label className="text-xs">SKU</Label>
                                <Input
                                    className="mt-1"
                                    placeholder="Buscar SKU..."
                                    value={skuInput}
                                    onChange={(e) =>
                                        setSkuInput(e.target.value)
                                    }
                                />
                            </div>
                            <div className="w-40">
                                <Label className="text-xs">Ubicación</Label>
                                <Input
                                    className="mt-1"
                                    placeholder="Código..."
                                    value={locationInput}
                                    onChange={(e) =>
                                        setLocationInput(e.target.value)
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
                                    Limpiar
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Table - Detail View */}
                {view === 'detail' && (
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cliente</TableHead>
                                        <TableHead>Almacén</TableHead>
                                        <TableHead>Ubicación</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="text-right">
                                            Cantidad
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Disponible
                                        </TableHead>
                                        <TableHead>UOM</TableHead>
                                        <TableHead>Lote</TableHead>
                                        <TableHead className="w-12"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={10}
                                                className="py-8 text-center text-muted-foreground"
                                            >
                                                No hay inventario
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        (items.data as InventoryItem[]).map(
                                            (item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell>
                                                        {item.customer?.name ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.warehouse?.name ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.location ? (
                                                            <Badge variant="outline">
                                                                {
                                                                    item
                                                                        .location
                                                                        .code
                                                                }
                                                            </Badge>
                                                        ) : (
                                                            <Badge
                                                                variant="outline"
                                                                className="border-amber-500/30 bg-amber-50 text-amber-700"
                                                            >
                                                                Sin ubicar
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {item.sku}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.description ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium">
                                                        {Number(
                                                            item.qty,
                                                        ).toLocaleString()}
                                                    </TableCell>
                                                    <TableCell className="text-right text-muted-foreground">
                                                        {Number(
                                                            item.available_qty ??
                                                                item.qty,
                                                        ).toLocaleString()}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.uom}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.lot_number || '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8"
                                                                >
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                {!item.location && (
                                                                    <DropdownMenuItem
                                                                        onClick={() =>
                                                                            openPutaway(
                                                                                item,
                                                                            )
                                                                        }
                                                                    >
                                                                        <MapPin className="mr-2 h-4 w-4" />
                                                                        Putaway
                                                                    </DropdownMenuItem>
                                                                )}
                                                                {item.location &&
                                                                    can(
                                                                        'inventory.transfer',
                                                                    ) && (
                                                                        <DropdownMenuItem
                                                                            onClick={() =>
                                                                                openRelocate(
                                                                                    item,
                                                                                )
                                                                            }
                                                                        >
                                                                            <ArrowRightLeft className="mr-2 h-4 w-4" />
                                                                            Reubicar
                                                                        </DropdownMenuItem>
                                                                    )}
                                                                {can(
                                                                    'inventory.adjust',
                                                                ) && (
                                                                    <DropdownMenuItem
                                                                        onClick={() =>
                                                                            openAdjust(
                                                                                item,
                                                                            )
                                                                        }
                                                                    >
                                                                        <PencilLine className="mr-2 h-4 w-4" />
                                                                        Ajustar
                                                                    </DropdownMenuItem>
                                                                )}
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    onClick={() =>
                                                                        openMovements(
                                                                            item,
                                                                        )
                                                                    }
                                                                >
                                                                    <History className="mr-2 h-4 w-4" />
                                                                    Historial
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Table - Summary View */}
                {view === 'summary' && (
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cliente</TableHead>
                                        <TableHead>Almacén</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="text-right">
                                            Qty Total
                                        </TableHead>
                                        <TableHead>UOM</TableHead>
                                        <TableHead className="text-right">
                                            Ubicaciones
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-8 text-center text-muted-foreground"
                                            >
                                                No hay inventario
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        (items.data as SummaryItem[]).map(
                                            (item, idx) => (
                                                <TableRow
                                                    key={`${item.customer_id}-${item.warehouse_id}-${item.sku}-${idx}`}
                                                >
                                                    <TableCell>
                                                        {item.customer?.name ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.warehouse?.name ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell className="font-medium">
                                                        {item.sku}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.description ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right text-lg font-bold">
                                                        {Number(
                                                            item.total_qty,
                                                        ).toLocaleString()}
                                                    </TableCell>
                                                    <TableCell>
                                                        {item.uom}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <Badge variant="secondary">
                                                            {item.item_count}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* Pagination */}
                {items.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {items.links.map((link, i) => (
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

            {/* Dialogs */}
            {selectedItem && (
                <>
                    <PutawayDialog
                        open={putawayOpen}
                        onClose={() => setPutawayOpen(false)}
                        itemId={selectedItem.id}
                        warehouseId={selectedItem.warehouse?.id}
                        sku={selectedItem.sku}
                    />
                    <RelocateDialog
                        open={relocateOpen}
                        onClose={() => setRelocateOpen(false)}
                        itemId={selectedItem.id}
                        warehouseId={selectedItem.warehouse?.id}
                        sku={selectedItem.sku}
                        currentLocationCode={selectedItem.location?.code || ''}
                        maxQty={selectedItem.qty}
                    />
                    <AdjustDialog
                        open={adjustOpen}
                        onClose={() => setAdjustOpen(false)}
                        itemId={selectedItem.id}
                        sku={selectedItem.sku}
                        currentQty={selectedItem.qty}
                    />
                    <MovementsDialog
                        open={movementsOpen}
                        onClose={() => setMovementsOpen(false)}
                        itemId={selectedItem.id}
                        sku={selectedItem.sku}
                    />
                </>
            )}
        </AppLayout>
    );
}
