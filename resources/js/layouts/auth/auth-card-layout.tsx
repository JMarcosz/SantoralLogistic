import AppLogo from '@/components/app-logo';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';
import { Link } from '@inertiajs/react';
import { Activity, ClipboardList, Shield, Truck } from 'lucide-react';
import { type PropsWithChildren } from 'react';

export default function AuthCardLayout({
    children,
    title,
    description,
}: PropsWithChildren<{
    title?: string;
    description?: string;
}>) {
    return (
        <div className="relative flex min-h-svh overflow-hidden bg-background">
            {/* Background */}
            <div className="motion-safe:animate-gradient absolute inset-0 bg-gradient-to-br from-primary/5 via-background to-accent/5 motion-reduce:animate-none" />
            <div className="absolute top-0 left-0 h-96 w-96 rounded-full bg-primary/10 blur-3xl motion-safe:animate-pulse motion-reduce:animate-none" />
            <div className="absolute right-0 bottom-0 h-96 w-96 rounded-full bg-accent/10 blur-3xl motion-safe:animate-pulse motion-reduce:animate-none" />

            <div className="relative z-10 flex w-full flex-col lg:flex-row">
                {/* Left: form */}
                <div className="flex flex-1 flex-col items-center justify-center p-6 md:p-10 lg:p-16">
                    <div className="motion-safe:animate-fade-in-up w-full max-w-md space-y-8 motion-reduce:animate-none">
                        <Link
                            href={home()}
                            className="group flex items-center justify-center gap-2 rounded-lg font-medium focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            aria-label="Volver al inicio"
                        >
                            <div className="flex h-12 items-center justify-center transition-transform duration-300 group-hover:scale-110">
                                <AppLogo />
                            </div>
                        </Link>

                        <Card className="glass-strong shadow-premium-xl border-primary/10">
                            <CardHeader className="space-y-2 px-8 pt-8 pb-6 text-center">
                                <CardTitle className="bg-gradient-to-r from-primary to-accent bg-clip-text text-2xl font-extrabold text-transparent">
                                    {title}
                                </CardTitle>
                                <CardDescription className="text-base">
                                    {description}
                                </CardDescription>
                            </CardHeader>

                            <CardContent className="px-8 pb-8">
                                {children}

                                {/* Internal notice */}
                                <div className="mt-6 rounded-lg border border-border/60 bg-card/60 p-3 text-xs text-muted-foreground">
                                    Acceso interno para{' '}
                                    <span className="font-semibold text-foreground">
                                        Maed Logistic Trading
                                    </span>
                                    . Si no tienes credenciales, contacta al
                                    administrador del sistema.
                                </div>
                            </CardContent>
                        </Card>

                        <p className="text-center text-xs text-muted-foreground">
                            © {new Date().getFullYear()} Stone Logistic
                            Platform · Maed Logistic Trading
                        </p>
                    </div>
                </div>

                {/* Right: brand/security panel */}
                <div className="relative hidden overflow-hidden lg:flex lg:flex-1">
                    <div className="motion-safe:animate-gradient absolute inset-0 bg-gradient-to-br from-primary via-primary/90 to-accent motion-reduce:animate-none" />

                    <div
                        className="absolute inset-0 opacity-10"
                        style={{
                            backgroundImage: `url("data:image/svg+xml,%3Csvg width='64' height='64' viewBox='0 0 64 64' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cpath d='M0 32h64M32 0v64' stroke='%23ffffff' stroke-opacity='0.25'/%3E%3C/g%3E%3C/svg%3E")`,
                        }}
                    />

                    <div className="relative z-10 flex flex-col justify-center p-16 text-primary-foreground">
                        <div className="space-y-10">
                            <div className="space-y-4">
                                <div className="inline-flex items-center gap-2 rounded-full border border-primary-foreground/20 bg-primary-foreground/10 px-4 py-1.5 text-sm font-semibold backdrop-blur-sm">
                                    <Shield className="h-4 w-4" />
                                    Acceso seguro · Uso interno
                                </div>

                                <h1 className="text-4xl leading-tight font-extrabold xl:text-5xl">
                                    Operación logística
                                    <br />
                                    <span className="text-primary-foreground/90">
                                        con control y trazabilidad
                                    </span>
                                </h1>

                                <p className="max-w-md text-lg text-primary-foreground/80">
                                    Ingresa para gestionar órdenes, almacén,
                                    tracking y reportes operativos. Control de
                                    acceso por roles y auditoría de acciones.
                                </p>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <FeatureCard
                                    icon={ClipboardList}
                                    title="Auditoría"
                                    description="Bitácora de acciones"
                                />
                                <FeatureCard
                                    icon={Truck}
                                    title="Tracking"
                                    description="Estados por eventos"
                                />
                                <FeatureCard
                                    icon={Activity}
                                    title="Operación"
                                    description="KPIs del día"
                                />
                                <FeatureCard
                                    icon={Shield}
                                    title="Roles"
                                    description="Accesos controlados"
                                />
                            </div>

                            <div className="text-xs text-primary-foreground/70">
                                Recomendación: usa un dispositivo confiable y no
                                compartas tus credenciales.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function FeatureCard({
    icon: Icon,
    title,
    description,
}: {
    icon: React.ComponentType<{ className?: string }>;
    title: string;
    description: string;
}) {
    return (
        <div className="group rounded-lg border border-primary-foreground/20 bg-primary-foreground/10 p-4 backdrop-blur-sm transition-all duration-300 hover:scale-[1.02] hover:bg-primary-foreground/15">
            <Icon className="mb-2 h-6 w-6 text-primary-foreground transition-transform duration-300 group-hover:scale-110" />
            <h3 className="mb-1 text-sm font-semibold">{title}</h3>
            <p className="text-xs text-primary-foreground/70">{description}</p>
        </div>
    );
}
