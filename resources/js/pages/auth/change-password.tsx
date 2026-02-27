import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, Eye, EyeOff, Loader2, Lock } from 'lucide-react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { useState } from 'react';

export default function ChangePassword() {
    const { data, setData, post, processing, errors } = useForm({
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmation, setShowConfirmation] = useState(false);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/change-password');
    };

    const handleLogout = () => {
        router.post('/logout');
    };

    return (
        <>
            <Head title="Cambiar Contraseña" />

            <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 px-4 dark:from-gray-900 dark:to-gray-950">
                <Card className="w-full max-w-md">
                    <CardHeader className="space-y-1 text-center">
                        <div className="mb-4 flex justify-center">
                            <div className="rounded-full bg-amber-100 p-3 dark:bg-amber-900/30">
                                <Lock className="h-8 w-8 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                        <CardTitle className="text-2xl font-bold">
                            Cambiar Contraseña
                        </CardTitle>
                        <CardDescription>
                            Por seguridad, debes cambiar tu contraseña temporal
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <Alert className="mb-6">
                            <AlertTriangle className="h-4 w-4" />
                            <AlertTitle>Contraseña Temporal</AlertTitle>
                            <AlertDescription>
                                Tu contraseña actual es temporal. Por favor,
                                define una nueva contraseña segura para
                                continuar.
                            </AlertDescription>
                        </Alert>

                        <form onSubmit={submit} className="space-y-4">
                            {/* New Password */}
                            <div className="space-y-2">
                                <Label htmlFor="password">
                                    Nueva Contraseña
                                    <span className="ml-1 text-destructive">
                                        *
                                    </span>
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={
                                            showPassword ? 'text' : 'password'
                                        }
                                        value={data.password}
                                        onChange={(e) =>
                                            setData('password', e.target.value)
                                        }
                                        placeholder="Mínimo 8 caracteres"
                                        required
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowPassword(!showPassword)
                                        }
                                        className="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                    >
                                        {showPassword ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="text-sm text-destructive">
                                        {errors.password}
                                    </p>
                                )}
                            </div>

                            {/* Confirm Password */}
                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">
                                    Confirmar Contraseña
                                    <span className="ml-1 text-destructive">
                                        *
                                    </span>
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="password_confirmation"
                                        type={
                                            showConfirmation
                                                ? 'text'
                                                : 'password'
                                        }
                                        value={data.password_confirmation}
                                        onChange={(e) =>
                                            setData(
                                                'password_confirmation',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Repite tu contraseña"
                                        required
                                        className="pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowConfirmation(
                                                !showConfirmation,
                                            )
                                        }
                                        className="absolute top-1/2 right-3 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                    >
                                        {showConfirmation ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </button>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="space-y-3 pt-4">
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Actualizando...
                                        </>
                                    ) : (
                                        <>
                                            <Lock className="mr-2 h-4 w-4" />
                                            Actualizar Contraseña
                                        </>
                                    )}
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    className="w-full"
                                    onClick={handleLogout}
                                    disabled={processing}
                                >
                                    Cerrar Sesión
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
