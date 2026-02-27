import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ProductService } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Boxes,
    Check,
    DollarSign,
    Edit,
    Plus,
    Receipt,
    Trash2,
    Wrench,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import ProductServiceFormDialog from './components/product-service-form-dialog';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface Props {
    productsServices: ProductService[];
    currencies: Currency[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/settings/profile' },
    { title: 'Productos y Servicios', href: '/settings/products-services' },
];

const typeLabels: Record<string, string> = {
    service: 'Servicio',
    product: 'Producto',
    fee: 'Cargo',
};

const typeColors: Record<string, string> = {
    service: 'bg-blue-500/10 text-blue-500 border-blue-500/30',
    product: 'bg-amber-500/10 text-amber-500 border-amber-500/30',
    fee: 'bg-purple-500/10 text-purple-500 border-purple-500/30',
};

export default function ProductsServicesIndex({
    productsServices,
    currencies,
}: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedItem, setSelectedItem] = useState<ProductService | null>(
        null,
    );
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<ProductService | null>(
        null,
    );
    const [typeFilter, setTypeFilter] = useState<string>('all');
    const [statusFilter, setStatusFilter] = useState<string>('all');

    const handleCreate = () => {
        setSelectedItem(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (item: ProductService) => {
        setSelectedItem(item);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (item: ProductService) => {
        setItemToDelete(item);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (itemToDelete) {
            router.delete(`/settings/products-services/${itemToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setItemToDelete(null);
                },
            });
        }
    };

    const filteredItems = useMemo(() => {
        let result = productsServices;

        if (typeFilter !== 'all') {
            result = result.filter((i) => i.type === typeFilter);
        }

        if (statusFilter === 'active') {
            result = result.filter((i) => i.is_active);
        } else if (statusFilter === 'inactive') {
            result = result.filter((i) => !i.is_active);
        }

        return result;
    }, [productsServices, typeFilter, statusFilter]);

    // Stats
    const totalItems = productsServices.length;
    const servicesCount = productsServices.filter(
        (i) => i.type === 'service',
    ).length;
    const productsCount = productsServices.filter(
        (i) => i.type === 'product',
    ).length;
    const feesCount = productsServices.filter((i) => i.type === 'fee').length;

    const columns: ColumnDef<ProductService>[] = [
        {
            accessorKey: 'code',
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                >
                    Código
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            ),
            cell: ({ row }) => (
                <span className="font-mono font-semibold">
                    {row.getValue('code')}
                </span>
            ),
        },
        {
            accessorKey: 'name',
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                >
                    Nombre
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            ),
        },
        {
            accessorKey: 'type',
            header: 'Tipo',
            cell: ({ row }) => {
                const type = row.getValue('type') as string;
                return (
                    <Badge className={typeColors[type]}>
                        {typeLabels[type] || type}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'uom',
            header: 'UOM',
            cell: ({ row }) => {
                const uom = row.getValue('uom') as string | null;
                return uom || '-';
            },
        },
        {
            id: 'default_price',
            header: 'Precio Base',
            cell: ({ row }) => {
                const item = row.original;
                if (!item.default_unit_price) return '-';
                const symbol = item.default_currency?.symbol || '$';
                return (
                    <span className="font-mono">
                        {symbol}
                        {Number(item.default_unit_price).toLocaleString(
                            'en-US',
                            { minimumFractionDigits: 2 },
                        )}
                    </span>
                );
            },
        },
        {
            accessorKey: 'taxable',
            header: 'Gravable',
            cell: ({ row }) => {
                const taxable = row.getValue('taxable') as boolean;
                return taxable ? (
                    <Badge className="gap-1 border-emerald-500/30 bg-emerald-500/10 text-emerald-500">
                        <Check className="h-3 w-3" />
                        Sí
                    </Badge>
                ) : (
                    <span className="text-muted-foreground">No</span>
                );
            },
        },
        {
            accessorKey: 'is_active',
            header: 'Estado',
            cell: ({ row }) => {
                const isActive = row.getValue('is_active') as boolean;
                return (
                    <Badge variant={isActive ? 'default' : 'secondary'}>
                        {isActive ? 'Activo' : 'Inactivo'}
                    </Badge>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const item = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(item)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteClick(item)}
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Productos y Servicios" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <Boxes className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Productos y Servicios
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Catálogo para cotizaciones y facturación
                                </p>
                            </div>
                        </div>

                        <Button onClick={handleCreate} className="shadow-md">
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Item
                        </Button>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-lg border border-violet-500/30 bg-gradient-to-br from-card to-violet-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Total
                            </p>
                            <Boxes className="h-4 w-4 text-violet-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{totalItems}</p>
                    </div>

                    <div className="rounded-lg border border-blue-500/30 bg-gradient-to-br from-card to-blue-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Servicios
                            </p>
                            <Wrench className="h-4 w-4 text-blue-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {servicesCount}
                        </p>
                    </div>

                    <div className="rounded-lg border border-amber-500/30 bg-gradient-to-br from-card to-amber-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Productos
                            </p>
                            <DollarSign className="h-4 w-4 text-amber-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {productsCount}
                        </p>
                    </div>

                    <div className="rounded-lg border border-purple-500/30 bg-gradient-to-br from-card to-purple-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Cargos
                            </p>
                            <Receipt className="h-4 w-4 text-purple-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{feesCount}</p>
                    </div>
                </div>

                {/* Filters and Table */}
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <Select
                            value={typeFilter}
                            onValueChange={setTypeFilter}
                        >
                            <SelectTrigger className="w-[140px]">
                                <SelectValue placeholder="Tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="service">
                                    Servicios
                                </SelectItem>
                                <SelectItem value="product">
                                    Productos
                                </SelectItem>
                                <SelectItem value="fee">Cargos</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            value={statusFilter}
                            onValueChange={setStatusFilter}
                        >
                            <SelectTrigger className="w-[140px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="active">Activos</SelectItem>
                                <SelectItem value="inactive">
                                    Inactivos
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <DataTable
                        columns={columns}
                        data={filteredItems}
                        searchKey="name"
                        searchPlaceholder="Buscar por nombre..."
                    />
                </div>
            </div>

            <ProductServiceFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                productService={selectedItem}
                currencies={currencies}
            />

            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará el item{' '}
                            <span className="font-semibold">
                                {itemToDelete?.name}
                            </span>
                            .
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDeleteConfirm}>
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
