import { Head, router, useForm } from '@inertiajs/react';
import { Loader2, Save } from 'lucide-react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import RoleRoutes from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';

interface Permission {
    name: string;
    label: string;
}

interface PermissionModule {
    module: string;
    label: string;
    permissions: Permission[];
}

interface Role {
    id: number;
    name: string;
}

interface Props {
    role: Role;
    permissions: PermissionModule[];
    currentPermissions: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: RoleRoutes.index().url,
    },
    {
        title: 'Editar Rol',
        href: '#',
    },
];

export default function EditRole({
    role,
    permissions,
    currentPermissions,
}: Props) {
    const { data, setData,  processing, errors } = useForm<{
        name: string;
        display_name: string;
        permissions: string[];
    }>({
        name: role.name || '',
        display_name:
            role.name
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (l) => l.toUpperCase()) || '',
        permissions: currentPermissions || [],
    });

    const togglePermission = (permissionName: string) => {
        if (data.permissions.includes(permissionName)) {
            setData(
                'permissions',
                data.permissions.filter((p) => p !== permissionName),
            );
        } else {
            setData('permissions', [...data.permissions, permissionName]);
        }
    };

    const toggleModule = (module: PermissionModule) => {
        const modulePermissions = module.permissions.map((p) => p.name);
        const allSelected = modulePermissions.every((p) =>
            data.permissions.includes(p),
        );

        if (allSelected) {
            // Deselect all
            setData(
                'permissions',
                data.permissions.filter((p) => !modulePermissions.includes(p)),
            );
        } else {
            // Select all
            const newPermissions = [
                ...data.permissions,
                ...modulePermissions.filter(
                    (p) => !data.permissions.includes(p),
                ),
            ];
            setData('permissions', newPermissions);
        }
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(RoleRoutes.update(role.id).url, data);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Rol - ${role.name}`} />

            <div className="container mx-auto max-w-3xl space-y-6 px-4 py-6">
                <HeadingSmall
                    title={`Editar Rol: ${role.name}`}
                    description="Modifica el nombre y permisos del rol."
                />

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardContent className="space-y-6 pt-6">
                            {/* Name (Read-only for system roles) */}
                            <div className="space-y-2">
                                <Label htmlFor="name">
                                    Nombre del Rol (slug){' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="ej: operations_manager"
                                    required
                                    disabled={role.name === 'super_admin'}
                                    className="h-11"
                                />
                                {role.name === 'super_admin' && (
                                    <p className="text-xs text-amber-600 dark:text-amber-400">
                                        El nombre del rol super_admin no se
                                        puede modificar
                                    </p>
                                )}
                                <InputError message={errors.name} />
                            </div>

                            {/* Display Name */}
                            <div className="space-y-2">
                                <Label htmlFor="display_name">
                                    Nombre para mostrar{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="display_name"
                                    value={data.display_name}
                                    onChange={(e) =>
                                        setData('display_name', e.target.value)
                                    }
                                    placeholder="ej: Gerente de Operaciones"
                                    required
                                    className="h-11"
                                />
                                <InputError message={errors.display_name} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Permissions */}
                    <Card>
                        <CardContent className="pt-6">
                            <Label className="text-base">Permisos</Label>
                            <p className="mb-4 text-sm text-muted-foreground">
                                Selecciona los permisos que tendrá este rol
                            </p>

                            <div className="space-y-6">
                                {permissions.map((module) => {
                                    const allSelected =
                                        module.permissions.every((p) =>
                                            data.permissions.includes(p.name),
                                        );
                                    const someSelected =
                                        module.permissions.some((p) =>
                                            data.permissions.includes(p.name),
                                        );

                                    return (
                                        <div key={module.module}>
                                            <div className="mb-3 flex items-center gap-2">
                                                <Checkbox
                                                    id={`module-${module.module}`}
                                                    checked={allSelected}
                                                    onCheckedChange={() =>
                                                        toggleModule(module)
                                                    }
                                                    className={
                                                        someSelected &&
                                                        !allSelected
                                                            ? 'data-[state=checked]:bg-primary/50'
                                                            : ''
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`module-${module.module}`}
                                                    className="cursor-pointer text-sm font-semibold"
                                                >
                                                    {module.label}
                                                </Label>
                                            </div>

                                            <div className="ml-6 grid grid-cols-2 gap-3">
                                                {module.permissions.map(
                                                    (perm) => (
                                                        <div
                                                            key={perm.name}
                                                            className="flex items-center gap-2"
                                                        >
                                                            <Checkbox
                                                                id={perm.name}
                                                                checked={data.permissions.includes(
                                                                    perm.name,
                                                                )}
                                                                onCheckedChange={() =>
                                                                    togglePermission(
                                                                        perm.name,
                                                                    )
                                                                }
                                                            />
                                                            <Label
                                                                htmlFor={
                                                                    perm.name
                                                                }
                                                                className="cursor-pointer text-sm"
                                                            >
                                                                {perm.label}
                                                            </Label>
                                                        </div>
                                                    ),
                                                )}
                                            </div>

                                            {module !==
                                                permissions[
                                                    permissions.length - 1
                                                ] && (
                                                <Separator className="mt-4" />
                                            )}
                                        </div>
                                    );
                                })}
                            </div>

                            <InputError
                                className="mt-2"
                                message={errors.permissions}
                            />
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Guardando...
                                </>
                            ) : (
                                <>
                                    <Save className="mr-2 h-4 w-4" />
                                    Guardar Cambios
                                </>
                            )}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href={RoleRoutes.index().url}>Cancelar</a>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
