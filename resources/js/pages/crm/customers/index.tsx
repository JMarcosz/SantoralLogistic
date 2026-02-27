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
import { type BreadcrumbItem, type Customer } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import {
    ArrowUpDown,
    Building2,
    Check,
    CreditCard,
    Edit,
    Eye,
    Mail,
    Phone,
    Plus,
    Trash2,
    Users,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import CustomerFormDialog from './components/customer-form-dialog';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface Props {
    customers: Customer[];
    currencies: Currency[];
    countries: string[];
    can: {
        create: boolean;
        update: boolean;
        delete: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/crm/customers' },
    { title: 'Clientes', href: '/crm/customers' },
];

const statusLabels: Record<string, string> = {
    prospect: 'Prospecto',
    active: 'Activo',
    inactive: 'Inactivo',
};

const statusColors: Record<string, string> = {
    prospect: 'bg-amber-500/10 text-amber-500 border-amber-500/30',
    active: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
    inactive: 'bg-slate-500/10 text-slate-500 border-slate-500/30',
};

export default function CustomersIndex({
    customers,
    currencies,
    countries,
    can,
}: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(
        null,
    );
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [customerToDelete, setCustomerToDelete] = useState<Customer | null>(
        null,
    );
    const [statusFilter, setStatusFilter] = useState<string>('all');
    const [countryFilter, setCountryFilter] = useState<string>('all');
    const [searchTerm, setSearchTerm] = useState('');

    const handleCreate = () => {
        setSelectedCustomer(null);
        setFormDialogOpen(true);
    };

    const handleEdit = (customer: Customer) => {
        setSelectedCustomer(customer);
        setFormDialogOpen(true);
    };

    const handleDeleteClick = (customer: Customer) => {
        setCustomerToDelete(customer);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        if (customerToDelete) {
            router.delete(`/crm/customers/${customerToDelete.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    setDeleteDialogOpen(false);
                    setCustomerToDelete(null);
                },
            });
        }
    };

    const filteredCustomers = useMemo(() => {
        let result = customers;

        // Status filter
        if (statusFilter !== 'all') {
            result = result.filter((c) => c.status === statusFilter);
        }

        // Country filter
        if (countryFilter !== 'all') {
            result = result.filter((c) => c.country === countryFilter);
        }

        // Search filter
        if (searchTerm) {
            const term = searchTerm.toLowerCase();
            result = result.filter(
                (c) =>
                    c.name.toLowerCase().includes(term) ||
                    c.code?.toLowerCase().includes(term) ||
                    c.tax_id?.toLowerCase().includes(term) ||
                    c.email_billing?.toLowerCase().includes(term),
            );
        }

        return result;
    }, [customers, statusFilter, countryFilter, searchTerm]);

    // Stats
    const totalCustomers = customers.length;
    const activeCount = customers.filter((c) => c.status === 'active').length;
    const prospectsCount = customers.filter(
        (c) => c.status === 'prospect',
    ).length;
    const withCreditLimit = customers.filter((c) => c.credit_limit).length;

    const columns: ColumnDef<Customer>[] = [
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
            cell: ({ row }) => {
                const code = row.getValue('code') as string | null;
                return code ? (
                    <span className="font-mono font-semibold">{code}</span>
                ) : (
                    <span className="text-muted-foreground">-</span>
                );
            },
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
            cell: ({ row }) => {
                const customer = row.original;
                return (
                    <div className="flex flex-col">
                        <Link
                            href={`/crm/customers/${customer.id}`}
                            className="font-medium hover:text-primary hover:underline"
                        >
                            {customer.name}
                        </Link>
                        {customer.tax_id && (
                            <span className="text-xs text-muted-foreground">
                                RNC: {customer.tax_id}
                            </span>
                        )}
                    </div>
                );
            },
        },
        {
            accessorKey: 'status',
            header: 'Estado',
            cell: ({ row }) => {
                const status = row.getValue('status') as string;
                return (
                    <Badge className={statusColors[status]}>
                        {statusLabels[status] || status}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'country',
            header: 'País',
            cell: ({ row }) => {
                const country = row.getValue('country') as string | null;
                return country || '-';
            },
        },
        {
            id: 'contact',
            header: 'Contacto',
            cell: ({ row }) => {
                const customer = row.original;
                return (
                    <div className="flex flex-col gap-0.5 text-sm">
                        {customer.email_billing && (
                            <div className="flex items-center gap-1 text-muted-foreground">
                                <Mail className="h-3 w-3" />
                                <span className="max-w-[180px] truncate">
                                    {customer.email_billing}
                                </span>
                            </div>
                        )}
                        {customer.phone && (
                            <div className="flex items-center gap-1 text-muted-foreground">
                                <Phone className="h-3 w-3" />
                                <span>{customer.phone}</span>
                            </div>
                        )}
                    </div>
                );
            },
        },
        {
            id: 'credit',
            header: 'Crédito',
            cell: ({ row }) => {
                const customer = row.original;
                if (!customer.credit_limit) return '-';
                const symbol = customer.currency?.symbol || '$';
                return (
                    <div className="flex items-center gap-1">
                        <CreditCard className="h-3.5 w-3.5 text-muted-foreground" />
                        <span className="font-mono text-sm">
                            {symbol}
                            {Number(customer.credit_limit).toLocaleString(
                                'en-US',
                                { minimumFractionDigits: 0 },
                            )}
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const customer = row.original;
                return (
                    <div className="flex justify-end gap-1">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={`/crm/customers/${customer.id}`}>
                                <Eye className="h-4 w-4" />
                            </Link>
                        </Button>
                        {can.update && (
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => handleEdit(customer)}
                            >
                                <Edit className="h-4 w-4" />
                            </Button>
                        )}
                        {can.delete && (
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => handleDeleteClick(customer)}
                            >
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        )}
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clientes" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <Building2 className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Clientes
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Gestión de clientes para cotizaciones y
                                    facturación
                                </p>
                            </div>
                        </div>

                        {can.create && (
                            <Button
                                onClick={handleCreate}
                                className="shadow-md"
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Cliente
                            </Button>
                        )}
                    </div>
                </div>

                {/* Stats */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-lg border border-violet-500/30 bg-gradient-to-br from-card to-violet-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Total
                            </p>
                            <Users className="h-4 w-4 text-violet-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {totalCustomers}
                        </p>
                    </div>

                    <div className="rounded-lg border border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Activos
                            </p>
                            <Check className="h-4 w-4 text-emerald-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">{activeCount}</p>
                    </div>

                    <div className="rounded-lg border border-amber-500/30 bg-gradient-to-br from-card to-amber-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Prospectos
                            </p>
                            <Users className="h-4 w-4 text-amber-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {prospectsCount}
                        </p>
                    </div>

                    <div className="rounded-lg border border-sky-500/30 bg-gradient-to-br from-card to-sky-500/5 p-4">
                        <div className="flex items-center justify-between">
                            <p className="text-sm text-muted-foreground">
                                Con Crédito
                            </p>
                            <CreditCard className="h-4 w-4 text-sky-500" />
                        </div>
                        <p className="mt-2 text-2xl font-bold">
                            {withCreditLimit}
                        </p>
                    </div>
                </div>

                {/* Filters and Table */}
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <Input
                            placeholder="Buscar por nombre, código o email..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-[300px]"
                        />

                        <Select
                            value={statusFilter}
                            onValueChange={setStatusFilter}
                        >
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="active">Activos</SelectItem>
                                <SelectItem value="prospect">
                                    Prospectos
                                </SelectItem>
                                <SelectItem value="inactive">
                                    Inactivos
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        {countries.length > 0 && (
                            <Select
                                value={countryFilter}
                                onValueChange={setCountryFilter}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="País" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todos los países
                                    </SelectItem>
                                    {countries.map((country) => (
                                        <SelectItem
                                            key={country}
                                            value={country}
                                        >
                                            {country}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
                    </div>

                    <DataTable columns={columns} data={filteredCustomers} />
                </div>
            </div>

            <CustomerFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                customer={selectedCustomer}
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
                            Esta acción eliminará al cliente{' '}
                            <span className="font-semibold">
                                {customerToDelete?.name}
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
