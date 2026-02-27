import { router, usePage } from '@inertiajs/react';
import {
    ArrowUpDown,
    Clock,
    Edit,
    Trash2,
    UserCheck,
    UserPlus,
    Users as UsersIcon,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import UserRoutes from '@/routes/users';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

interface User {
    id: number;
    name: string;
    email: string;
    role_names: string[];
    created_at: string;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    users: PaginatedUsers;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: UserRoutes.index().url,
    },
];

export default function UsersIndex({ users }: Props) {
    const { auth } = usePage<SharedData>().props;

    const handleDelete = (user: User) => {
        if (
            confirm(
                `¿Estás seguro de eliminar al usuario ${user.name}? Esta acción no se puede deshacer.`,
            )
        ) {
            router.delete(UserRoutes.destroy(user.id).url);
        }
    };

    const canCreate =
        auth.user?.role_names?.includes('super_admin') ||
        auth.user?.role_names?.includes('manager');

    // Calculate stats
    const totalUsers = users.data.length;
    const activeUsers = users.data.filter((u) => u.id !== auth.user.id).length;
    const recentUsers = users.data.filter((u) => {
        const createdDate = new Date(u.created_at);
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        return createdDate > thirtyDaysAgo;
    }).length;

    // Column definitions for DataTable
    const columns: ColumnDef<User>[] = [
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
            cell: ({ row }) => (
                <span className="font-medium">{row.getValue('name')}</span>
            ),
        },
        {
            accessorKey: 'email',
            header: ({ column }) => {
                return (
                    <Button
                        variant="ghost"
                        onClick={() =>
                            column.toggleSorting(column.getIsSorted() === 'asc')
                        }
                    >
                        Email
                        <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                );
            },
            cell: ({ row }) => (
                <span className="text-muted-foreground">
                    {row.getValue('email')}
                </span>
            ),
        },
        {
            accessorKey: 'role_names',
            header: 'Rol(es)',
            cell: ({ row }) => {
                const roles = row.getValue('role_names') as string[];
                return <Badge variant="secondary">{roles.join(', ')}</Badge>;
            },
        },
        {
            accessorKey: 'created_at',
            header: 'Fecha Creación',
            cell: ({ row }) => {
                const date = new Date(row.getValue('created_at'));
                return (
                    <span className="text-sm text-muted-foreground">
                        {date.toLocaleDateString('es-ES')}
                    </span>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const user = row.original;
                return (
                    <div className="flex items-center justify-end gap-2">
                        <Button variant="ghost" size="sm" asChild>
                            <a href={UserRoutes.edit(user.id).url}>
                                <Edit className="h-4 w-4" />
                            </a>
                        </Button>
                        {user.id !== auth.user.id && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleDelete(user)}
                            >
                                <Trash2 className="h-4 w-4 text-destructive" />
                            </Button>
                        )}
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Premium Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-primary/5 blur-2xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-start gap-6">
                            {/* Icon Circle */}
                            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <UsersIcon className="h-10 w-10 text-primary-foreground" />
                            </div>

                            {/* Title and Description */}
                            <div className="space-y-2">
                                <h1 className="text-4xl font-bold tracking-tight">
                                    Usuarios
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Gestiona los usuarios del sistema
                                </p>
                            </div>
                        </div>

                        {/* Action Button */}
                        {canCreate && (
                            <Button asChild size="lg" className="shadow-lg">
                                <a href={UserRoutes.create().url}>
                                    <UserPlus className="mr-2 h-5 w-5" />
                                    Nuevo Usuario
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-6 md:grid-cols-3">
                    {/* Total Users */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-primary/30 bg-gradient-to-br from-card to-primary/5 p-6 transition-all hover:border-primary/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-primary/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total Usuarios
                                </p>
                                <div className="rounded-lg bg-primary/10 p-2">
                                    <UsersIcon className="h-5 w-5 text-primary" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {totalUsers}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                usuarios registrados
                            </p>
                        </div>
                    </div>

                    {/* Active Users */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-emerald-500/30 bg-gradient-to-br from-card to-emerald-500/5 p-6 transition-all hover:border-emerald-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-emerald-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Usuarios Activos
                                </p>
                                <div className="rounded-lg bg-emerald-500/10 p-2">
                                    <UserCheck className="h-5 w-5 text-emerald-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {activeUsers}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                usuarios activos
                            </p>
                        </div>
                    </div>

                    {/* Recent Users */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-cyan-500/30 bg-gradient-to-br from-card to-cyan-500/5 p-6 transition-all hover:border-cyan-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-cyan-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Usuarios Recientes
                                </p>
                                <div className="rounded-lg bg-cyan-500/10 p-2">
                                    <Clock className="h-5 w-5 text-cyan-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {recentUsers}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                últimos 30 días
                            </p>
                        </div>
                    </div>
                </div>

                {/* DataTable */}
                <DataTable
                    columns={columns}
                    data={users.data}
                    searchKey="email"
                    searchPlaceholder="Buscar por email..."
                />
            </div>
        </AppLayout>
    );
}
