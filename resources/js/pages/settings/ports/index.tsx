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
import { type BreadcrumbItem, type Port } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    Anchor,
    ArrowUpDown,
    Edit,
    Globe,
    Plane,
    Plus,
    Trash2,
    Truck,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import PortFormDialog from './components/port-form-dialog';

interface Props {
    ports: Port[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/settings/profile',
    },
    {
        title: 'Puertos',
        href: '/settings/ports',
    },
];

const typeLabels: Record<Port['type'], string> = {
    air: 'Aéreo',
    ocean: 'Marítimo',
    ground: 'Terrestre',
};

const typeIcons: Record<Port['type'], React.ReactNode> = {
    air: <Plane className="h-4 w-4" />,
    ocean: <Anchor className="h-4 w-4" />,
    ground: <Truck className="h-4 w-4" />,
};

const typeColors: Record<Port['type'], string> = {
    air: 'bg-sky-500/10 text-sky-500 border-sky-500/30',
    ocean: 'bg-blue-500/10 text-blue-500 border-blue-500/30',
    ground: 'bg-amber-500/10 text-amber-500 border-amber-500/30',
};

export default function PortsIndex({ ports }: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedPort, setSelectedPort] = useState<Port | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [portToDelete, setPortToDelete] = useState<Port | null>(null);
    const [typeFilter, setTypeFilter] = useState<string>('all');

    const handleCreate = () => {
        setSelectedPort(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (port: Port) => {
        setSelectedPort(port);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (port: Port) => {
        setPortToDelete(port);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (portToDelete) {
            router.delete(`/settings/ports/${portToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setPortToDelete(null);
                },
            });
        }
    };

    // Filter ports by type
    const filteredPorts = useMemo(() => {
        if (typeFilter === 'all') return ports;
        return ports.filter((p) => p.type === typeFilter);
    }, [ports, typeFilter]);

    // Calculate stats
    const totalPorts = ports.length;
    const airPorts = ports.filter((p) => p.type === 'air').length;
    const oceanPorts = ports.filter((p) => p.type === 'ocean').length;
    const groundPorts = ports.filter((p) => p.type === 'ground').length;

    // Column definitions for DataTable
    const columns: ColumnDef<Port>[] = [
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
            accessorKey: 'country',
            header: ({ column }) => {
                return (
                    <Button
                        variant="ghost"
                        onClick={() =>
                            column.toggleSorting(column.getIsSorted() === 'asc')
                        }
                    >
                        País
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                );
            },
        },
        {
            accessorKey: 'city',
            header: 'Ciudad',
            cell: ({ row }) => row.getValue('city') || '-',
        },
        {
            accessorKey: 'type',
            header: 'Tipo',
            cell: ({ row }) => {
                const type = row.getValue('type') as Port['type'];
                return (
                    <Badge
                        variant="outline"
                        className={`gap-1 ${typeColors[type]}`}
                    >
                        {typeIcons[type]}
                        {typeLabels[type]}
                    </Badge>
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
                const port = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(port)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteClick(port)}
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
            <Head title="Puertos" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Premium Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-primary/5 blur-2xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-start gap-6">
                            {/* Icon Circle */}
                            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <Globe className="h-10 w-10 text-primary-foreground" />
                            </div>

                            {/* Title and Description */}
                            <div className="space-y-2">
                                <h1 className="text-4xl font-bold tracking-tight">
                                    Gestión de Puertos
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Administra puertos, aeropuertos y terminales
                                    terrestres
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
                            Nuevo Puerto
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-6 md:grid-cols-4">
                    {/* Total Ports */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-violet-500/30 bg-gradient-to-br from-card to-violet-500/5 p-6 transition-all hover:border-violet-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-violet-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total Puertos
                                </p>
                                <div className="rounded-lg bg-violet-500/10 p-2">
                                    <Globe className="h-5 w-5 text-violet-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {totalPorts}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                ubicaciones registradas
                            </p>
                        </div>
                    </div>

                    {/* Air Ports */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-sky-500/30 bg-gradient-to-br from-card to-sky-500/5 p-6 transition-all hover:border-sky-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-sky-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Aeropuertos
                                </p>
                                <div className="rounded-lg bg-sky-500/10 p-2">
                                    <Plane className="h-5 w-5 text-sky-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {airPorts}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                transporte aéreo
                            </p>
                        </div>
                    </div>

                    {/* Ocean Ports */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-blue-500/30 bg-gradient-to-br from-card to-blue-500/5 p-6 transition-all hover:border-blue-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-blue-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Puertos Marítimos
                                </p>
                                <div className="rounded-lg bg-blue-500/10 p-2">
                                    <Anchor className="h-5 w-5 text-blue-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {oceanPorts}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                transporte marítimo
                            </p>
                        </div>
                    </div>

                    {/* Ground Locations */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-amber-500/30 bg-gradient-to-br from-card to-amber-500/5 p-6 transition-all hover:border-amber-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-amber-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Terminales Terrestres
                                </p>
                                <div className="rounded-lg bg-amber-500/10 p-2">
                                    <Truck className="h-5 w-5 text-amber-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {groundPorts}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                transporte terrestre
                            </p>
                        </div>
                    </div>
                </div>

                {/* Filter and DataTable */}
                <div className="space-y-4">
                    <div className="flex items-center gap-4">
                        <Select
                            value={typeFilter}
                            onValueChange={setTypeFilter}
                        >
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="Filtrar por tipo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todos los tipos
                                </SelectItem>
                                <SelectItem value="air">
                                    <div className="flex items-center gap-2">
                                        <Plane className="h-4 w-4" />
                                        Aéreo
                                    </div>
                                </SelectItem>
                                <SelectItem value="ocean">
                                    <div className="flex items-center gap-2">
                                        <Anchor className="h-4 w-4" />
                                        Marítimo
                                    </div>
                                </SelectItem>
                                <SelectItem value="ground">
                                    <div className="flex items-center gap-2">
                                        <Truck className="h-4 w-4" />
                                        Terrestre
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <DataTable
                        columns={columns}
                        data={filteredPorts}
                        searchKey="name"
                        searchPlaceholder="Buscar por nombre..."
                    />
                </div>
            </div>

            {/* Port Form Dialog */}
            <PortFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                port={selectedPort}
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
                            Esta acción eliminará el puerto{' '}
                            <span className="font-semibold">
                                {portToDelete?.name}
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
