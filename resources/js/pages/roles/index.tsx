import { router, usePage } from '@inertiajs/react';
import {
    ArrowUpDown,
    Edit,
    Key,
    Shield,
    ShieldPlus,
    Trash2,
    Users,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import RoleRoutes from '@/routes/roles';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';

interface Role {
    id: number;
    name: string;
    users_count: number;
    permissions_count: number;
}

interface PaginatedRoles {
    data: Role[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    roles: PaginatedRoles;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: RoleRoutes.index().url,
    },
];

export default function RolesIndex({ roles }: Props) {
    const { auth } = usePage<SharedData>().props;

    const handleDelete = (role: Role) => {
        if (role.name === 'super_admin') {
            alert('No se puede eliminar el rol super_admin');
            return;
        }

        if (role.users_count > 0) {
            alert(
                `No se puede eliminar este rol porque tiene ${role.users_count} usuario(s) asignado(s).`,
            );
            return;
        }

        if (
            confirm(
                `¿Estás seguro de eliminar el rol ${role.name}? Esta acción no se puede deshacer.`,
            )
        ) {
            router.delete(RoleRoutes.destroy(role.id).url);
        }
    };

    const canCreate = auth.user?.role_names?.includes('super_admin');

    // Calculate stats
    const totalRoles = roles.data.length;
    const totalUsers = roles.data.reduce(
        (sum, role) => sum + role.users_count,
        0,
    );
    const totalPermissions = roles.data.reduce(
        (sum, role) => sum + role.permissions_count,
        0,
    );

    // Column definitions for DataTable
    const columns: ColumnDef<Role>[] = [
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
            cell: ({ row }) => {
                const name = row.getValue('name') as string;
                return (
                    <div className="flex items-center gap-2">
                        <span className="font-medium capitalize">
                            {name.replace(/_/g, ' ')}
                        </span>
                        {name === 'super_admin' && (
                            <Badge
                                variant="outline"
                                className="bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                            >
                                Sistema
                            </Badge>
                        )}
                    </div>
                );
            },
        },
        {
            accessorKey: 'permissions_count',
            header: () => <div className="text-center">Permisos</div>,
            cell: ({ row }) => {
                const count = row.getValue('permissions_count') as number;
                return (
                    <div className="text-center">
                        <Badge
                            variant="secondary"
                            className="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                        >
                            {count}
                        </Badge>
                    </div>
                );
            },
        },
        {
            accessorKey: 'users_count',
            header: () => <div className="text-center">Usuarios Asignados</div>,
            cell: ({ row }) => {
                const count = row.getValue('users_count') as number;
                return (
                    <div className="text-center">
                        <Badge
                            variant="secondary"
                            className="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"
                        >
                            {count}
                        </Badge>
                    </div>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const role = row.original;
                return (
                    <div className="flex items-center justify-end gap-2">
                        <Button variant="ghost" size="sm" asChild>
                            <a href={RoleRoutes.edit(role.id).url}>
                                <Edit className="h-4 w-4" />
                            </a>
                        </Button>
                        {role.name !== 'super_admin' && canCreate && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleDelete(role)}
                                disabled={role.users_count > 0}
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
            <Head title="Roles" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Premium Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-primary/5 blur-2xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-start gap-6">
                            {/* Icon Circle */}
                            <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <Shield className="h-10 w-10 text-primary-foreground" />
                            </div>

                            {/* Title and Description */}
                            <div className="space-y-2">
                                <h1 className="text-4xl font-bold tracking-tight">
                                    Roles y Permisos
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Define qué pueden hacer los usuarios en el
                                    sistema
                                </p>
                            </div>
                        </div>

                        {/* Action Button */}
                        {canCreate && (
                            <Button asChild size="lg" className="shadow-lg">
                                <a href={RoleRoutes.create().url}>
                                    <ShieldPlus className="mr-2 h-5 w-5" />
                                    Crear Rol
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-6 md:grid-cols-3">
                    {/* Total Roles */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-primary/30 bg-gradient-to-br from-card to-primary/5 p-6 transition-all hover:border-primary/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-primary/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total de Roles
                                </p>
                                <div className="rounded-lg bg-primary/10 p-2">
                                    <Shield className="h-5 w-5 text-primary" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {totalRoles}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                roles activos
                            </p>
                        </div>
                    </div>

                    {/* Total Users */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-blue-500/30 bg-gradient-to-br from-card to-blue-500/5 p-6 transition-all hover:border-blue-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-blue-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Total de Usuarios
                                </p>
                                <div className="rounded-lg bg-blue-500/10 p-2">
                                    <Users className="h-5 w-5 text-blue-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {totalUsers}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                usuarios asignados
                            </p>
                        </div>
                    </div>

                    {/* Total Permissions */}
                    <div className="group shadow-premium-md hover:shadow-premium-lg relative overflow-hidden rounded-xl border-2 border-purple-500/30 bg-gradient-to-br from-card to-purple-500/5 p-6 transition-all hover:border-purple-500/50">
                        <div className="absolute top-0 right-0 h-24 w-24 translate-x-6 -translate-y-6 rounded-full bg-purple-500/10 blur-2xl" />
                        <div className="relative space-y-3">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">
                                    Permisos Asignados
                                </p>
                                <div className="rounded-lg bg-purple-500/10 p-2">
                                    <Key className="h-5 w-5 text-purple-500" />
                                </div>
                            </div>
                            <p className="text-4xl font-bold tracking-tight">
                                {totalPermissions}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                permisos totales
                            </p>
                        </div>
                    </div>
                </div>

                {/* DataTable */}
                <DataTable
                    columns={columns}
                    data={roles.data}
                    searchKey="name"
                    searchPlaceholder="Buscar rol..."
                />
            </div>
        </AppLayout>
    );
}
