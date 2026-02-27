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
import { type BreadcrumbItem, type Rate } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowRight,
    ArrowUpDown,
    Calculator,
    Check,
    Edit,
    Plus,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import RateFormDialog from './components/rate-form-dialog';

interface Port {
    id: number;
    code: string;
    name: string;
    type: string;
}

interface TransportMode {
    id: number;
    code: string;
    name: string;
}

interface ServiceType {
    id: number;
    code: string;
    name: string;
}

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface Props {
    rates: Rate[];
    ports: Port[];
    transportModes: TransportMode[];
    serviceTypes: ServiceType[];
    currencies: Currency[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/settings/profile' },
    { title: 'Tarifas', href: '/settings/rates' },
];

const chargeBasisLabels: Record<string, string> = {
    per_shipment: 'Por Embarque',
    per_kg: 'Por Kg',
    per_cbm: 'Por CBM',
    per_container: 'Por Contenedor',
};

export default function RatesIndex({
    rates,
    ports,
    transportModes,
    serviceTypes,
    currencies,
}: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedRate, setSelectedRate] = useState<Rate | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [rateToDelete, setRateToDelete] = useState<Rate | null>(null);
    const [modeFilter, setModeFilter] = useState<string>('all');
    const [statusFilter, setStatusFilter] = useState<string>('all');

    const handleCreate = () => {
        setSelectedRate(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (rate: Rate) => {
        setSelectedRate(rate);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (rate: Rate) => {
        setRateToDelete(rate);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (rateToDelete) {
            router.delete(`/settings/rates/${rateToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setRateToDelete(null);
                },
            });
        }
    };

    const filteredRates = useMemo(() => {
        let result = rates;

        if (modeFilter !== 'all') {
            result = result.filter(
                (r) => r.transport_mode_id.toString() === modeFilter,
            );
        }

        if (statusFilter === 'active') {
            result = result.filter((r) => r.is_active);
        } else if (statusFilter === 'inactive') {
            result = result.filter((r) => !r.is_active);
        }

        return result;
    }, [rates, modeFilter, statusFilter]);

    // Stats
    const totalRates = rates.length;
    const activeRates = rates.filter((r) => r.is_active).length;
    const validRates = rates.filter((r) => {
        const today = new Date().toISOString().split('T')[0];
        return r.valid_from <= today && (!r.valid_to || r.valid_to >= today);
    }).length;

    const columns: ColumnDef<Rate>[] = [
        {
            id: 'lane',
            header: ({ column }) => (
                <Button
                    variant="ghost"
                    onClick={() =>
                        column.toggleSorting(column.getIsSorted() === 'asc')
                    }
                >
                    Carril
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            ),
            cell: ({ row }) => {
                const rate = row.original;
                return (
                    <div className="flex items-center gap-2">
                        <span className="font-mono font-semibold">
                            {rate.origin_port?.code}
                        </span>
                        <ArrowRight className="h-4 w-4 text-muted-foreground" />
                        <span className="font-mono font-semibold">
                            {rate.destination_port?.code}
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'mode_service',
            header: 'Modo / Servicio',
            cell: ({ row }) => {
                const rate = row.original;
                return (
                    <div className="flex items-center gap-2">
                        <Badge variant="outline">
                            {rate.transport_mode?.code}
                        </Badge>
                        <span className="text-muted-foreground">/</span>
                        <Badge variant="secondary">
                            {rate.service_type?.code}
                        </Badge>
                    </div>
                );
            },
        },
        {
            id: 'amount',
            header: 'Tarifa',
            cell: ({ row }) => {
                const rate = row.original;
                const symbol = rate.currency?.symbol || '$';
                return (
                    <div className="flex flex-col">
                        <span className="font-mono font-semibold">
                            {symbol}
                            {Number(rate.base_amount).toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                            })}
                        </span>
                        <span className="text-xs text-muted-foreground">
                            {chargeBasisLabels[rate.charge_basis] ||
                                rate.charge_basis}
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'min',
            header: 'Mínimo',
            cell: ({ row }) => {
                const rate = row.original;
                if (!rate.min_amount) return '-';
                const symbol = rate.currency?.symbol || '$';
                return (
                    <span className="font-mono text-sm">
                        {symbol}
                        {Number(rate.min_amount).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                        })}
                    </span>
                );
            },
        },
        {
            id: 'validity',
            header: 'Vigencia',
            cell: ({ row }) => {
                const rate = row.original;
                const today = new Date().toISOString().split('T')[0];
                const isValid =
                    rate.valid_from <= today &&
                    (!rate.valid_to || rate.valid_to >= today);

                return (
                    <div className="flex flex-col gap-0.5">
                        <span className="text-sm">
                            {new Date(rate.valid_from).toLocaleDateString()}
                            {rate.valid_to &&
                                ` - ${new Date(rate.valid_to).toLocaleDateString()}`}
                        </span>
                        {isValid ? (
                            <Badge className="w-fit gap-1 border-emerald-500/30 bg-emerald-500/10 text-emerald-500">
                                <Check className="h-3 w-3" />
                                Vigente
                            </Badge>
                        ) : (
                            <Badge variant="secondary" className="w-fit">
                                {rate.valid_from > today
                                    ? 'Pendiente'
                                    : 'Expirado'}
                            </Badge>
                        )}
                    </div>
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
                const rate = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(rate)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteClick(rate)}
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
            <Head title="Tarifas" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <Calculator className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Tarifas
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Precios base por carril y servicio
                                </p>
                            </div>
                        </div>

                        <Button onClick={handleCreate} className="shadow-md">
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Tarifa
                        </Button>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="rounded-lg border border-violet-500/30 bg-gradient-to-br from-card to-violet-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Total
                            </p>
                            <Calculator className="h-4 w-4 text-violet-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{totalRates}</p>
                    </div>

                    <div className="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Activos
                            </p>
                            <Check className="h-4 w-4 text-emerald-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{activeRates}</p>
                    </div>

                    <div className="rounded-lg border border-sky-500/30 bg-gradient-to-br from-card to-sky-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Vigentes
                            </p>
                            <Check className="h-4 w-4 text-sky-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{validRates}</p>
                    </div>
                </div>

                {/* Filters and Table */}
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <Select
                            value={modeFilter}
                            onValueChange={setModeFilter}
                        >
                            <SelectTrigger className="w-[160px]">
                                <SelectValue placeholder="Modo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todos los modos
                                </SelectItem>
                                {transportModes.map((mode) => (
                                    <SelectItem
                                        key={mode.id}
                                        value={mode.id.toString()}
                                    >
                                        {mode.name}
                                    </SelectItem>
                                ))}
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

                    <DataTable columns={columns} data={filteredRates} />
                </div>
            </div>

            <RateFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                rate={selectedRate}
                ports={ports}
                transportModes={transportModes}
                serviceTypes={serviceTypes}
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
                            Esta acción eliminará la tarifa.
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
