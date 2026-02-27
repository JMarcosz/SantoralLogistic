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
import { type BreadcrumbItem, type PackageType } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Box,
    Container,
    Edit,
    Package,
    Plus,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import PackageTypeFormDialog from './components/package-type-form-dialog';

interface Props {
    packageTypes: PackageType[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/settings/profile',
    },
    {
        title: 'Tipos de Paquete',
        href: '/settings/package-types',
    },
];

const categoryLabels: Record<string, string> = {
    box: 'Caja',
    pallet: 'Pallet',
    container: 'Contenedor',
    envelope: 'Sobre',
    other: 'Otro',
};

export default function PackageTypesIndex({ packageTypes }: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedPackageType, setSelectedPackageType] =
        useState<PackageType | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [packageTypeToDelete, setPackageTypeToDelete] =
        useState<PackageType | null>(null);
    const [categoryFilter, setCategoryFilter] = useState<string>('all');
    const [statusFilter, setStatusFilter] = useState<string>('all');

    const handleCreate = () => {
        setSelectedPackageType(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (packageType: PackageType) => {
        setSelectedPackageType(packageType);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (packageType: PackageType) => {
        setPackageTypeToDelete(packageType);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (packageTypeToDelete) {
            router.delete(`/settings/package-types/${packageTypeToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setPackageTypeToDelete(null);
                },
            });
        }
    };

    // Filter package types
    const filteredPackageTypes = useMemo(() => {
        let result = packageTypes;

        if (categoryFilter !== 'all') {
            result = result.filter((pt) => pt.category === categoryFilter);
        }

        if (statusFilter === 'active') {
            result = result.filter((pt) => pt.is_active);
        } else if (statusFilter === 'inactive') {
            result = result.filter((pt) => !pt.is_active);
        }

        return result;
    }, [packageTypes, categoryFilter, statusFilter]);

    // Stats
    const totalTypes = packageTypes.length;
    const boxCount = packageTypes.filter((pt) => pt.category === 'box').length;
    const containerCount = packageTypes.filter((pt) => pt.is_container).length;
    const activeCount = packageTypes.filter((pt) => pt.is_active).length;

    // Column definitions
    const columns: ColumnDef<PackageType>[] = [
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
            accessorKey: 'category',
            header: 'Categoría',
            cell: ({ row }) => {
                const category = row.getValue('category') as string | null;
                if (!category) return '-';
                return (
                    <Badge variant="outline" className="capitalize">
                        {categoryLabels[category] || category}
                    </Badge>
                );
            },
        },
        {
            id: 'dimensions',
            header: 'Dimensiones',
            cell: ({ row }) => {
                const { length_cm, width_cm, height_cm } = row.original;
                if (length_cm && width_cm && height_cm) {
                    return (
                        <span className="text-sm text-muted-foreground">
                            {length_cm} × {width_cm} × {height_cm} cm
                        </span>
                    );
                }
                return '-';
            },
        },
        {
            accessorKey: 'max_weight_kg',
            header: 'Peso Máx.',
            cell: ({ row }) => {
                const weight = row.getValue('max_weight_kg') as number | null;
                if (!weight) return '-';
                return `${weight} kg`;
            },
        },
        {
            accessorKey: 'is_container',
            header: 'Contenedor',
            cell: ({ row }) => {
                const isContainer = row.getValue('is_container') as boolean;
                return isContainer ? (
                    <Badge className="gap-1 border-sky-500/30 bg-sky-500/10 text-sky-500">
                        <Container className="h-3 w-3" />
                        Sí
                    </Badge>
                ) : (
                    <span className="text-muted-foreground">-</span>
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
                const packageType = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(packageType)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteClick(packageType)}
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
            <Head title="Tipos de Paquete" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Compact Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <Package className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Tipos de Paquete
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Gestiona cajas, pallets, contenedores y
                                    empaques
                                </p>
                            </div>
                        </div>

                        <Button onClick={handleCreate} className="shadow-md">
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Tipo
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-lg border border-violet-500/30 bg-gradient-to-br from-card to-violet-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Total
                            </p>
                            <Package className="h-4 w-4 text-violet-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{totalTypes}</p>
                    </div>

                    <div className="rounded-lg border border-amber-500/30 bg-gradient-to-br from-card to-amber-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Cajas
                            </p>
                            <Box className="h-4 w-4 text-amber-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{boxCount}</p>
                    </div>

                    <div className="rounded-lg border border-sky-500/30 bg-gradient-to-br from-card to-sky-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Contenedores
                            </p>
                            <Container className="h-4 w-4 text-sky-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {containerCount}
                        </p>
                    </div>

                    <div className="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Activos
                            </p>
                            <Package className="h-4 w-4 text-emerald-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{activeCount}</p>
                    </div>
                </div>

                {/* Filters and DataTable */}
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <Select
                            value={categoryFilter}
                            onValueChange={setCategoryFilter}
                        >
                            <SelectTrigger className="w-[160px]">
                                <SelectValue placeholder="Categoría" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todas las categorías
                                </SelectItem>
                                <SelectItem value="box">Caja</SelectItem>
                                <SelectItem value="pallet">Pallet</SelectItem>
                                <SelectItem value="container">
                                    Contenedor
                                </SelectItem>
                                <SelectItem value="envelope">Sobre</SelectItem>
                                <SelectItem value="other">Otro</SelectItem>
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
                        data={filteredPackageTypes}
                        searchKey="name"
                        searchPlaceholder="Buscar por nombre..."
                    />
                </div>
            </div>

            <PackageTypeFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                packageType={selectedPackageType}
            />

            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará el tipo de paquete{' '}
                            <span className="font-semibold">
                                {packageTypeToDelete?.name}
                            </span>
                            . Esta acción no se puede deshacer.
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
