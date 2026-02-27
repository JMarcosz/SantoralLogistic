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
import AppLayout from '@/layouts/app-layout';
import currencyRoutes from '@/routes/currencies';
import { type BreadcrumbItem, type Currency } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Coins,
    DollarSign,
    Edit,
    Globe,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import CurrencyFormDialog from './components/currency-form-dialog';

interface Props {
    currencies: Currency[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/settings/profile',
    },
    {
        title: 'Monedas',
        href: '/settings/currencies',
    },
];

export default function CurrenciesIndex({ currencies }: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedCurrency, setSelectedCurrency] = useState<Currency | null>(
        null,
    );
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [currencyToDelete, setCurrencyToDelete] = useState<Currency | null>(
        null,
    );

    const handleCreate = () => {
        setSelectedCurrency(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (currency: Currency) => {
        setSelectedCurrency(currency);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (currency: Currency) => {
        setCurrencyToDelete(currency);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (currencyToDelete) {
            router.delete(currencyRoutes.destroy(currencyToDelete.id).url, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setCurrencyToDelete(null);
                },
            });
        }
    };

    // Calculate stats
    const totalCurrencies = currencies.length;
    const defaultCurrency = currencies.find((c) => c.is_default);
    const activeCurrencies = currencies.filter((c) => !c.is_default).length;

    // Column definitions for DataTable
    const columns: ColumnDef<Currency>[] = [
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
            accessorKey: 'symbol',
            header: 'Símbolo',
            cell: ({ row }) => (
                <span className="font-medium">{row.getValue('symbol')}</span>
            ),
        },
        {
            accessorKey: 'is_default',
            header: 'Estado',
            cell: ({ row }) => {
                const isDefault = row.getValue('is_default') as boolean;
                return (
                    <Badge variant={isDefault ? 'default' : 'secondary'}>
                        {isDefault ? 'Por Defecto' : 'Secundaria'}
                    </Badge>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const currency = row.original;
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleEdit(currency)}
                        >
                            <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => handleDeleteClick(currency)}
                            disabled={currency.is_default}
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
            <Head title="Monedas" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Premium Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-primary/5 blur-2xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-start gap-6">
                            {/* Icon Circle */}
                            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <Coins className="h-10 w-10 text-primary-foreground" />
                            </div>

                            {/* Title and Description */}
                            <div className="space-y-2">
                                <h1 className="text-4xl font-bold tracking-tight">
                                    Gestión de Monedas
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Administra las monedas disponibles en el
                                    sistema
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
                            Nueva Moneda
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-6 md:grid-cols-3">
                    {/* Total Currencies */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-amber-500/30 bg-gradient-to-br from-card to-amber-500/5 p-6 transition-all hover:border-amber-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-amber-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total de Monedas
                                </p>
                                <div className="rounded-lg bg-amber-500/10 p-2">
                                    <Coins className="h-5 w-5 text-amber-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {totalCurrencies}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                monedas configuradas
                            </p>
                        </div>
                    </div>

                    {/* Default Currency */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-6 transition-all hover:border-emerald-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-emerald-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Moneda Principal
                                </p>
                                <div className="rounded-lg bg-emerald-500/10 p-2">
                                    <DollarSign className="h-5 w-5 text-emerald-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {defaultCurrency?.code || '-'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {defaultCurrency?.name || 'No definida'}
                            </p>
                        </div>
                    </div>

                    {/* Secondary Currencies */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-sky-500/30 bg-gradient-to-br from-card to-sky-500/5 p-6 transition-all hover:border-sky-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-sky-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Monedas Secundarias
                                </p>
                                <div className="rounded-lg bg-sky-500/10 p-2">
                                    <Globe className="h-5 w-5 text-sky-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {activeCurrencies}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                monedas alternativas
                            </p>
                        </div>
                    </div>
                </div>

                {/* DataTable */}
                <DataTable
                    columns={columns}
                    data={currencies}
                    searchKey="code"
                    searchPlaceholder="Buscar por código..."
                />
            </div>

            {/* Currency Form Dialog */}
            <CurrencyFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                currency={selectedCurrency}
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
                            Esta acción eliminará la moneda{' '}
                            <span className="font-semibold">
                                {currencyToDelete?.name}
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
