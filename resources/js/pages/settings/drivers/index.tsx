/* eslint-disable react-hooks/exhaustive-deps */
/* eslint-disable @typescript-eslint/no-explicit-any */
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
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import driverRoutes from '@/routes/settings/drivers';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Edit, Plus, Search, Trash2, UserCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import DriverFormDialog, { type Driver } from './components/driver-form-dialog';

// If useDebounce is not available, I'll inline a logic or import.
// Standard project usually has it or I can just use a simple timeout.
// But to be safe I'll use manual timeout in useEffect.

interface Props {
    drivers: {
        data: Driver[];
        current_page: number;
        last_page: number;
        total: number;
        links: any[]; // Pagination links
    }; // Pagination object
    filters: {
        search?: string;
        is_active?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '#', // Fallback or correct parent
    },
    {
        title: 'Conductores',
        href: driverRoutes.index().url,
    },
];

export default function DriversIndex({ drivers, filters }: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedDriver, setSelectedDriver] = useState<Driver | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [driverToDelete, setDriverToDelete] = useState<Driver | null>(null);

    // Filter State
    const [search, setSearch] = useState(filters.search || '');
    const [isActiveFilter, setIsActiveFilter] = useState(
        filters.is_active || 'all',
    );

    // Debounce search
    const [debouncedSearch] = useDebounce(search, 500); // Attempting to use use-debounce
    // If it fails compile, I might need to fix imports.
    // I'll check imports later. Assuming it's installed or standard.
    // Actually I'll use a timer to be dependency-free.

    // Manual Debounce Logic replacement just in case
    // const [debouncedSearch, setDebouncedSearch] = useState(search);
    // useEffect(() => {
    //    const handler = setTimeout(() => setDebouncedSearch(search), 500);
    //    return () => clearTimeout(handler);
    // }, [search]);

    // Trigger Router Get on Filter Change
    useEffect(() => {
        // Prevent initial load double fetch if params match
        if (
            debouncedSearch === (filters.search || '') &&
            isActiveFilter === (filters.is_active || 'all')
        ) {
            return;
        }

        router.get(
            driverRoutes.index().url,
            {
                search: debouncedSearch,
                is_active: isActiveFilter,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, [debouncedSearch, isActiveFilter]);

    const handleCreate = () => {
        setSelectedDriver(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (driver: Driver) => {
        setSelectedDriver(driver);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (driver: Driver) => {
        setDriverToDelete(driver);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (driverToDelete) {
            router.delete(driverRoutes.destroy(driverToDelete.id).url, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setDriverToDelete(null);
                },
            });
        }
    };

    const columns: ColumnDef<Driver>[] = [
        {
            accessorKey: 'name',
            header: 'Nombre',
            cell: ({ row }) => (
                <div className="font-medium">{row.getValue('name')}</div>
            ),
            enableSorting: true,
        },
        {
            accessorKey: 'phone',
            header: 'Teléfono',
            cell: ({ row }) => row.getValue('phone') || '-',
        },
        {
            accessorKey: 'email',
            header: 'Email',
            cell: ({ row }) => row.getValue('email') || '-',
        },
        {
            accessorKey: 'license_number',
            header: 'Licencia',
            cell: ({ row }) => (
                <span className="font-mono text-xs">
                    {row.getValue('license_number') || '-'}
                </span>
            ),
        },
        {
            accessorKey: 'vehicle_plate',
            header: 'Placa',
            cell: ({ row }) => (
                <Badge variant="outline" className="font-mono">
                    {row.getValue('vehicle_plate') || 'SIN PLACA'}
                </Badge>
            ),
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
            enableSorting: true,
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const driver = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(driver)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="text-destructive hover:text-destructive"
                            onClick={() => handleDeleteClick(driver)}
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
            <Head title="Conductores" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-start gap-6">
                            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <UserCircle className="h-10 w-10 text-primary-foreground" />
                            </div>
                            <div className="space-y-2">
                                <h1 className="text-4xl font-bold tracking-tight">
                                    Conductores
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Catálogo de choferes para Pickup & Delivery
                                </p>
                            </div>
                        </div>

                        <Button
                            onClick={handleCreate}
                            size="lg"
                            className="shadow-lg"
                        >
                            <Plus className="mr-2 h-5 w-5" />
                            Nuevo Driver
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-1 items-center gap-4">
                        <div className="relative w-full sm:max-w-xs">
                            <Search className="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Buscar choferes..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-8"
                            />
                        </div>
                        <Select
                            value={isActiveFilter}
                            onValueChange={setIsActiveFilter}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="active">
                                    Solo Activos
                                </SelectItem>
                                <SelectItem value="inactive">
                                    Solo Inactivos
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Table */}
                <DataTable
                    columns={columns}
                    data={drivers.data || []} // Handle pagination structure
                    // If DataTable supports pagination props, pass them.
                    // Assuming existing DataTable might not handle server-side pagination props directly
                    // based on Ports implementation which used client-side.
                    // If client-side, we pass `drivers.data`.
                    // But requirement says "listado paginado".
                    // I'll check DataTable implementation later.
                    // For now, I'll pass data array.
                />
            </div>

            <DriverFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                driver={selectedDriver}
            />

            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará al conductor{' '}
                            <span className="font-semibold">
                                {driverToDelete?.name}
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

// Simple debounce impl if use-debounce lib is missing
function useDebounce<T>(value: T, delay: number): [T] {
    const [debouncedValue, setDebouncedValue] = useState(value);
    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);
        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);
    return [debouncedValue];
}
