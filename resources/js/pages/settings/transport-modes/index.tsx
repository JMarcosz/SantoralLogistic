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
import { type BreadcrumbItem, type TransportMode } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Check,
    Edit,
    Plane,
    Plus,
    Ship,
    Trash2,
    Truck,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import TransportModeFormDialog from './components/transport-mode-form-dialog';

interface Props {
    transportModes: TransportMode[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/settings/profile' },
    { title: 'Modos de Transporte', href: '/settings/transport-modes' },
];

export default function TransportModesIndex({ transportModes }: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedMode, setSelectedMode] = useState<TransportMode | null>(
        null,
    );
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [modeToDelete, setModeToDelete] = useState<TransportMode | null>(
        null,
    );
    const [statusFilter, setStatusFilter] = useState<string>('all');

    const handleCreate = () => {
        setSelectedMode(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (mode: TransportMode) => {
        setSelectedMode(mode);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (mode: TransportMode) => {
        setModeToDelete(mode);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (modeToDelete) {
            router.delete(`/settings/transport-modes/${modeToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setModeToDelete(null);
                },
            });
        }
    };

    const filteredModes = useMemo(() => {
        if (statusFilter === 'all') return transportModes;
        if (statusFilter === 'active')
            return transportModes.filter((m) => m.is_active);
        return transportModes.filter((m) => !m.is_active);
    }, [transportModes, statusFilter]);

    // Stats
    const totalModes = transportModes.length;
    const activeModes = transportModes.filter((m) => m.is_active).length;
    const awbModes = transportModes.filter((m) => m.supports_awb).length;
    const blModes = transportModes.filter((m) => m.supports_bl).length;

    const getModeIcon = (code: string) => {
        switch (code.toUpperCase()) {
            case 'AIR':
                return <Plane className="h-4 w-4" />;
            case 'OCEAN':
                return <Ship className="h-4 w-4" />;
            default:
                return <Truck className="h-4 w-4" />;
        }
    };

    const columns: ColumnDef<TransportMode>[] = [
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
                <div className="flex items-center gap-2">
                    {getModeIcon(row.getValue('code'))}
                    <span className="font-mono font-semibold">
                        {row.getValue('code')}
                    </span>
                </div>
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
            accessorKey: 'supports_awb',
            header: 'AWB',
            cell: ({ row }) => {
                const supports = row.getValue('supports_awb') as boolean;
                return supports ? (
                    <Badge className="gap-1 border-sky-500/30 bg-sky-500/10 text-sky-500">
                        <Check className="h-3 w-3" />
                        Sí
                    </Badge>
                ) : (
                    <span className="text-muted-foreground">-</span>
                );
            },
        },
        {
            accessorKey: 'supports_bl',
            header: 'B/L',
            cell: ({ row }) => {
                const supports = row.getValue('supports_bl') as boolean;
                return supports ? (
                    <Badge className="gap-1 border-indigo-500/30 bg-indigo-500/10 text-indigo-500">
                        <Check className="h-3 w-3" />
                        Sí
                    </Badge>
                ) : (
                    <span className="text-muted-foreground">-</span>
                );
            },
        },
        {
            accessorKey: 'supports_pod',
            header: 'POD',
            cell: ({ row }) => {
                const supports = row.getValue('supports_pod') as boolean;
                return supports ? (
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
                const mode = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(mode)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteClick(mode)}
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
            <Head title="Modos de Transporte" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <Truck className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Modos de Transporte
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Aéreo, Marítimo, Terrestre y más
                                </p>
                            </div>
                        </div>

                        <Button onClick={handleCreate} className="shadow-md">
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Modo
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
                            <Truck className="h-4 w-4 text-violet-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{totalModes}</p>
                    </div>

                    <div className="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Activos
                            </p>
                            <Check className="h-4 w-4 text-emerald-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{activeModes}</p>
                    </div>

                    <div className="rounded-lg border border-sky-500/30 bg-gradient-to-br from-card to-sky-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Con AWB
                            </p>
                            <Plane className="h-4 w-4 text-sky-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{awbModes}</p>
                    </div>

                    <div className="rounded-lg border border-indigo-500/30 bg-gradient-to-br from-card to-indigo-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Con B/L
                            </p>
                            <Ship className="h-4 w-4 text-indigo-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{blModes}</p>
                    </div>
                </div>

                {/* Filter and Table */}
                <div className="space-y-4">
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
                            <SelectItem value="inactive">Inactivos</SelectItem>
                        </SelectContent>
                    </Select>

                    <DataTable
                        columns={columns}
                        data={filteredModes}
                        searchKey="name"
                        searchPlaceholder="Buscar por nombre..."
                    />
                </div>
            </div>

            <TransportModeFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                transportMode={selectedMode}
            />

            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará el modo de transporte{' '}
                            <span className="font-semibold">
                                {modeToDelete?.name}
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
