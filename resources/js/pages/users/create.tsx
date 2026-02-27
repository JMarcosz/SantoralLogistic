import { Head, useForm } from '@inertiajs/react';
import { Info, Loader2, UserPlus } from 'lucide-react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import UserRoutes from '@/routes/users';
import { type BreadcrumbItem } from '@/types';

interface Role {
    value: string;
    label: string;
}

interface Props {
    roles: Role[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: UserRoutes.index().url,
    },
    {
        title: 'Crear Usuario',
        href: UserRoutes.create().url,
    },
];

export default function CreateUser({ roles }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        role: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(UserRoutes.store().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Usuario" />

            <div className="container mx-auto max-w-2xl space-y-6 px-4 py-6">
                <HeadingSmall
                    title="Crear Nuevo Usuario"
                    description="Completa la información para crear un nuevo usuario en el sistema."
                />

                <form onSubmit={submit}>
                    <Card>
                        <CardContent className="space-y-6 pt-6">
                            {/* Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name">
                                    Nombre Completo{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Juan Pérez"
                                    required
                                    className="h-11"
                                />
                                <InputError message={errors.name} />
                            </div>

                            {/* Email */}
                            <div className="space-y-2">
                                <Label htmlFor="email">
                                    Correo Electrónico{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    placeholder="juan@ejemplo.com"
                                    required
                                    className="h-11"
                                />
                                <InputError message={errors.email} />
                            </div>

                            {/* Alert - Password Auto-generated */}
                            <Alert>
                                <Info className="h-4 w-4" />
                                <AlertDescription>
                                    Se generará automáticamente una contraseña
                                    temporal y se enviará por correo electrónico
                                    al usuario.
                                </AlertDescription>
                            </Alert>

                            {/* Role */}
                            <div className="space-y-2">
                                <Label htmlFor="role">
                                    Rol{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.role}
                                    onValueChange={(value) =>
                                        setData('role', value)
                                    }
                                >
                                    <SelectTrigger className="h-11">
                                        <SelectValue placeholder="Seleccionar rol..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem
                                                key={role.value}
                                                value={role.value}
                                            >
                                                {role.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role} />
                            </div>

                            {/* Actions */}
                            <div className="flex items-center gap-4 border-t pt-4">
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Creando...
                                        </>
                                    ) : (
                                        <>
                                            <UserPlus className="mr-2 h-4 w-4" />
                                            Crear Usuario
                                        </>
                                    )}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <a href={UserRoutes.index().url}>
                                        Cancelar
                                    </a>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
