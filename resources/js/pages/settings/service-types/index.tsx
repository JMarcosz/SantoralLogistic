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
import { type BreadcrumbItem, type ServiceType } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Check, Edit, Layers, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import ServiceTypeFormDialog from './components/service-type-form-dialog';

interface Props {
    serviceTypes: ServiceType[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/settings/profile',
    },
    {
        title: 'Tipos de Servicio',
        href: '/settings/service-types',
    },
];

export default function ServiceTypesIndex({ serviceTypes }: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedServiceType, setSelectedServiceType] =
        useState<ServiceType | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [serviceTypeToDelete, setServiceTypeToDelete] =
        useState<ServiceType | null>(null);
    const [statusFilter, setStatusFilter] = useState<string>('all');

    const handleCreate = () => {
        setSelectedServiceType(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (serviceType: ServiceType) => {
        setSelectedServiceType(serviceType);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (serviceType: ServiceType) => {
        setServiceTypeToDelete(serviceType);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (serviceTypeToDelete) {
            router.delete(`/settings/service-types/${serviceTypeToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setServiceTypeToDelete(null);
                },
            });
        }
    };

    // Filter service types by status
    const filteredServiceTypes = useMemo(() => {
        if (statusFilter === 'all') return serviceTypes;
        if (statusFilter === 'active')
            return serviceTypes.filter((st) => st.is_active);
        return serviceTypes.filter((st) => !st.is_active);
    }, [serviceTypes, statusFilter]);

    // Calculate stats
    const totalTypes = serviceTypes.length;
    const activeTypes = serviceTypes.filter((st) => st.is_active).length;
    const inactiveTypes = serviceTypes.filter((st) => !st.is_active).length;
    const defaultType = serviceTypes.find((st) => st.is_default);

    // Column definitions for DataTable
    const columns: ColumnDef<ServiceType>[] = [
        {
            accessorKey: 'code',
            header: ({ column }) => {
                return (
                    <Button
                        variant="ghost"
                        onClick={() =>
                            column.toggleSorting(column.getIsSorted() === 'asc')
                        }
                    >
                        Código
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                );
            },
            cell: ({ row }) => (
                <span className="font-mono font-semibold">
                    {row.getValue('code')}
                </span>
            ),
        },
        {
            accessorKey: 'name',
            header: ({ column }) => {
                return (
                    <Button
                        variant="ghost"
                        onClick={() =>
                            column.toggleSorting(column.getIsSorted() === 'asc')
                        }
                    >
                        Nombre
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                );
            },
        },
        {
            accessorKey: 'scope',
            header: 'Alcance',
            cell: ({ row }) => {
                const scope = row.getValue('scope') as string | null;
                if (!scope) return '-';
                return (
                    <Badge variant="outline" className="capitalize">
                        {scope}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'default_incoterm',
            header: 'Incoterm',
            cell: ({ row }) => row.getValue('default_incoterm') || '-',
        },
        {
            accessorKey: 'is_default',
            header: 'Predeterminado',
            cell: ({ row }) => {
                const isDefault = row.getValue('is_default') as boolean;
                return isDefault ? (
                    <Badge className="gap-1 border-emerald-500/30 bg-emerald-500/10 text-emerald-500">
                        <Check className="h-3 w-3" />
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
                const serviceType = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(serviceType)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteClick(serviceType)}
                            disabled={serviceType.is_default}
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
            <Head title="Tipos de Servicio" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Premium Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-primary/5 blur-2xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-start gap-6">
                            {/* Icon Circle */}
                            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <Layers className="h-10 w-10 text-primary-foreground" />
                            </div>

                            {/* Title and Description */}
                            <div className="space-y-2">
                                <h1 className="text-4xl font-bold tracking-tight">
                                    Tipos de Servicio
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Administra los tipos de servicio logístico
                                    (D2D, P2D, etc.)
                                </p>
                            </div>
                        </div>

                        {/* Action Button */}
                        <Button
                            onClick={handleCreate}
                            size="lg"
                            className="shadow-lg"
                        >
                            <Plus className="mr-2 h-5 w-5" />
                            Nuevo Tipo
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-6 md:grid-cols-4">
                    {/* Total Types */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-violet-500/30 bg-gradient-to-br from-card to-violet-500/5 p-6 transition-all hover:border-violet-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-violet-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total Tipos
                                </p>
                                <div className="rounded-lg bg-violet-500/10 p-2">
                                    <Layers className="h-5 w-5 text-violet-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {totalTypes}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                tipos de servicio
                            </p>
                        </div>
                    </div>

                    {/* Active Types */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-6 transition-all hover:border-emerald-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-emerald-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Activos
                                </p>
                                <div className="rounded-lg bg-emerald-500/10 p-2">
                                    <Check className="h-5 w-5 text-emerald-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {activeTypes}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                disponibles
                            </p>
                        </div>
                    </div>

                    {/* Inactive Types */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-orange-500/30 bg-gradient-to-br from-card to-orange-500/5 p-6 transition-all hover:border-orange-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-orange-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Inactivos
                                </p>
                                <div className="rounded-lg bg-orange-500/10 p-2">
                                    <Layers className="h-5 w-5 text-orange-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {inactiveTypes}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                deshabilitados
                            </p>
                        </div>
                    </div>

                    {/* Default Type */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-sky-500/30 bg-gradient-to-br from-card to-sky-500/5 p-6 transition-all hover:border-sky-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-sky-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Predeterminado
                                </p>
                                <div className="rounded-lg bg-sky-500/10 p-2">
                                    <Check className="h-5 w-5 text-sky-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {defaultType?.code || '-'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {defaultType?.name || 'No definido'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Filter and DataTable */}
                <div className="space-y-4">
                    <div className="flex items-center gap-4">
                        <Select
                            value={statusFilter}
                            onValueChange={setStatusFilter}
                        >
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="Filtrar por estado" />
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
                        data={filteredServiceTypes}
                        searchKey="name"
                        searchPlaceholder="Buscar por nombre..."
                    />
                </div>
            </div>

            {/* Service Type Form Dialog */}
            <ServiceTypeFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                serviceType={selectedServiceType}
            />

            {/* Delete Confirmation Dialog */}
            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará el tipo de servicio{' '}
                            <span className="font-semibold">
                                {serviceTypeToDelete?.name}
                            </span>{' '}
                            del sistema. Esta acción no se puede deshacer.
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
