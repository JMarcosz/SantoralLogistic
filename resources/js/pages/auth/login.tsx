import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/react';
import { AlertCircle, Eye, EyeOff, Lock, Mail } from 'lucide-react';
import React from 'react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean; // se ignora por ser app interna
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const [showPassword, setShowPassword] = React.useState(false);

    return (
        <AuthLayout
            title="Acceso seguro"
            description="Introduce tu correo y contraseña para entrar al sistema"
        >
            <Head title="Iniciar sesión" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => {
                    const hasAuthError = Boolean(
                        errors.email || errors.password,
                    );

                    return (
                        <>
                            <div className="grid gap-6">
                                {hasAuthError && (
                                    <div
                                        className="motion-safe:animate-shake motion-safe:animate-fade-in flex items-start gap-3 rounded-lg border border-destructive/20 bg-destructive/10 p-4 text-sm text-destructive motion-reduce:animate-none"
                                        role="alert"
                                        aria-live="polite"
                                    >
                                        <AlertCircle className="mt-0.5 h-5 w-5 flex-shrink-0" />
                                        <div>
                                            <p className="font-semibold">
                                                Credenciales inválidas
                                            </p>
                                            <p className="mt-1 text-xs text-destructive/80">
                                                Verifica tu correo y contraseña
                                                e intenta nuevamente.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* Email */}
                                <div className="motion-safe:animate-fade-in-up grid gap-2 motion-reduce:animate-none">
                                    <Label
                                        htmlFor="email"
                                        className="text-sm font-semibold"
                                    >
                                        Correo electrónico
                                    </Label>

                                    <div className="group relative">
                                        <Mail className="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-muted-foreground transition-colors duration-200 group-focus-within:text-primary" />
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="email"
                                            placeholder="correo@maedlogistic.com"
                                            aria-invalid={
                                                Boolean(errors.email) ||
                                                undefined
                                            }
                                            className="h-11 pl-10 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        />
                                    </div>

                                    {errors.email && (
                                        <p className="text-xs text-destructive">
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                {/* Password */}
                                <div className="motion-safe:animate-fade-in-up grid gap-2 motion-reduce:animate-none">
                                    <div className="flex items-center justify-between">
                                        <Label
                                            htmlFor="password"
                                            className="text-sm font-semibold"
                                        >
                                            Contraseña
                                        </Label>

                                        {canResetPassword && (
                                            <TextLink
                                                href={request()}
                                                className="hover:text-primary-hover text-sm font-semibold text-primary transition-colors"
                                                tabIndex={5}
                                            >
                                                ¿Olvidaste tu contraseña?
                                            </TextLink>
                                        )}
                                    </div>

                                    <div className="group relative">
                                        <Lock className="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-muted-foreground transition-colors duration-200 group-focus-within:text-primary" />

                                        <Input
                                            id="password"
                                            type={
                                                showPassword
                                                    ? 'text'
                                                    : 'password'
                                            }
                                            name="password"
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            placeholder="• • • • • • • •"
                                            aria-invalid={
                                                Boolean(errors.password) ||
                                                undefined
                                            }
                                            className="h-11 pr-12 pl-10 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        />

                                        <button
                                            type="button"
                                            onClick={() =>
                                                setShowPassword((v) => !v)
                                            }
                                            className="absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-2 text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                            aria-label={
                                                showPassword
                                                    ? 'Ocultar contraseña'
                                                    : 'Mostrar contraseña'
                                            }
                                            tabIndex={6}
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>

                                    {errors.password && (
                                        <p className="text-xs text-destructive">
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                {/* Remember */}
                                <div className="flex items-start gap-3">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
                                        className="mt-0.5 data-[state=checked]:border-primary data-[state=checked]:bg-primary"
                                    />
                                    <div>
                                        <Label
                                            htmlFor="remember"
                                            className="cursor-pointer text-sm font-semibold select-none"
                                        >
                                            Recordarme en este dispositivo
                                        </Label>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Úsalo solo en un dispositivo
                                            confiable.
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    className="group relative mt-2 h-11 w-full overflow-hidden text-base font-extrabold transition-all duration-300 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.99]"
                                    tabIndex={4}
                                    disabled={processing}
                                    data-test="login-button"
                                >
                                    <span className="absolute inset-0 bg-gradient-to-r from-primary to-primary opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                                    <span className="relative flex items-center justify-center gap-2">
                                        {processing && (
                                            <Spinner className="h-4 w-4" />
                                        )}
                                        {processing
                                            ? 'Entrando...'
                                            : 'Iniciar sesión'}
                                    </span>
                                </Button>
                            </div>
                        </>
                    );
                }}
            </Form>

            {status && (
                <div
                    className="border-success/20 bg-success/10 text-success motion-safe:animate-fade-in mt-4 rounded-lg border p-3 text-center text-sm font-semibold motion-reduce:animate-none"
                    role="status"
                    aria-live="polite"
                >
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}
