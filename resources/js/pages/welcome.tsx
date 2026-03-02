import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    CheckCircle,
    ClipboardList,
    MapPinned,
    Package,
    Shield,
    Truck,
    Zap,
} from 'lucide-react';

type FeatureAccent = {
    glowFrom: string;
    iconBg: string;
    iconBorder: string;
    iconText: string;
};

type FeatureItem = {
    icon: React.ComponentType<{ className?: string }>;
    title: string;
    description: string;
    accent: FeatureAccent;
};

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    // IMPORTANT: no dynamic Tailwind classes here (build-safe)
    const features: FeatureItem[] = [
        {
            icon: ClipboardList,
            title: 'Órdenes y Operación',
            description:
                'Crea y gestiona órdenes, estados operativos, SLA, incidencias y bitácoras por evento.',
            accent: {
                glowFrom: 'from-primary/10',
                iconBg: 'bg-primary/10',
                iconBorder: 'border-primary/20',
                iconText: 'text-primary',
            },
        },
        {
            icon: Truck,
            title: 'Envíos y Tracking',
            description:
                'Seguimiento por hitos: recibido, en almacén, en ruta, entregado, pendiente o retenido.',
            accent: {
                glowFrom: 'from-accent/10',
                iconBg: 'bg-accent/10',
                iconBorder: 'border-accent/20',
                iconText: 'text-accent',
            },
        },
        {
            icon: Package,
            title: 'Almacén e Inventario',
            description:
                'Recepciones, ubicaciones, existencias y alertas por stock crítico o discrepancias.',
            accent: {
                glowFrom: 'from-emerald-500/10',
                iconBg: 'bg-emerald-500/10',
                iconBorder: 'border-emerald-500/20',
                iconText: 'text-emerald-600 dark:text-emerald-400',
            },
        },
        {
            icon: BarChart3,
            title: 'Reportes Operativos',
            description:
                'KPIs operativos: volumen por estado, entregas del día, incidencias y productividad.',
            accent: {
                glowFrom: 'from-amber-500/10',
                iconBg: 'bg-amber-500/10',
                iconBorder: 'border-amber-500/20',
                iconText: 'text-amber-600 dark:text-amber-400',
            },
        },
        {
            icon: Shield,
            title: 'Roles y Auditoría',
            description:
                'Control de acceso por roles, trazabilidad y auditoría de cambios para operación crítica.',
            accent: {
                glowFrom: 'from-cyan-500/10',
                iconBg: 'bg-cyan-500/10',
                iconBorder: 'border-cyan-500/20',
                iconText: 'text-cyan-600 dark:text-cyan-400',
            },
        },
        {
            icon: MapPinned,
            title: 'Rutas y Entregas',
            description:
                'Planificación de rutas, asignación de entregas y control de ejecución en campo.',
            accent: {
                glowFrom: 'from-sky-500/10',
                iconBg: 'bg-sky-500/10',
                iconBorder: 'border-sky-500/20',
                iconText: 'text-sky-600 dark:text-sky-400',
            },
        },
    ];

    const capabilities = [
        {
            title: 'Trazabilidad end-to-end',
            desc: 'Estados y eventos por envío',
        },
        {
            title: 'Bitácora y auditoría',
            desc: 'Registro de cambios y acciones',
        },
        { title: 'Roles por área', desc: 'Operación, almacén, facturación' },
        { title: 'Multi-almacén', desc: 'Ubicaciones y existencias' },
    ];

    return (
        <>
            <Head title="Bienvenido">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap"
                    rel="stylesheet"
                />
                <link rel="preload" href="/favicon.svg" as="image" />
            </Head>

            <div className="relative min-h-screen overflow-hidden bg-gradient-to-br from-background via-primary/5 to-accent/5 font-sans">
                {/* Background elements */}
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div className="absolute -top-40 -left-40 h-80 w-80 rounded-full bg-primary/10 blur-3xl motion-safe:animate-pulse motion-reduce:animate-none" />
                    <div className="absolute top-1/4 -right-40 h-96 w-96 rounded-full bg-accent/10 blur-3xl motion-safe:animate-pulse motion-reduce:animate-none" />
                    <div className="absolute -bottom-40 left-1/3 h-80 w-80 rounded-full bg-primary/5 blur-3xl motion-safe:animate-pulse motion-reduce:animate-none" />
                </div>

                {/* Header */}
                <header className="glass-strong shadow-premium-sm relative z-10 border-b border-border">
                    <div className="mx-auto max-w-7xl px-6 py-4 lg:px-8">
                        <nav
                            className="flex items-center justify-between"
                            aria-label="Top navigation"
                        >
                            <div className="flex items-center space-x-3">
                                <div className="shadow-premium-md glow-primary flex h-10 w-10 items-center justify-center rounded-lg">
                                    <AppLogoIcon glow={true} />
                                </div>

                                <div className="leading-tight">
                                    <div className="text-xl font-extrabold text-foreground">
                                        Stone Logistic Platform
                                    </div>
                                    <div className="text-xs font-medium text-muted-foreground">
                                        ERP operativo para Maed Logistic Trading
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="gradient-primary hover-lift group shadow-premium-md transition-smooth hover:shadow-premium-lg relative overflow-hidden rounded-lg px-6 py-2.5 font-semibold text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        <span className="relative z-10 flex items-center gap-2">
                                            Ir al Dashboard
                                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                        </span>
                                    </Link>
                                ) : (
                                    <Link
                                        href={login()}
                                        className="rounded-lg px-5 py-2 font-semibold text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        Iniciar sesión
                                    </Link>
                                )}
                            </div>
                        </nav>
                    </div>
                </header>

                {/* Hero */}
                <section className="relative z-10 px-6 pt-16 pb-12 lg:px-8 lg:pt-28 lg:pb-20">
                    <div className="mx-auto max-w-7xl">
                        <div className="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                            {/* Copy */}
                            <div className="motion-safe:animate-fade-in-up space-y-8 motion-reduce:animate-none">
                                <div className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-4 py-1.5 text-sm font-semibold text-primary backdrop-blur-sm">
                                    <Zap className="h-4 w-4" />
                                    <span>
                                        Operación logística, controlada y
                                        trazable
                                    </span>
                                </div>

                                <h1 className="text-5xl leading-tight font-extrabold tracking-tight text-foreground sm:text-6xl lg:text-7xl">
                                    Controla cada envío,{' '}
                                    <span className="bg-gradient-to-r from-primary via-accent to-primary bg-clip-text text-transparent">
                                        desde recepción hasta entrega
                                    </span>
                                </h1>

                                <p className="text-xl leading-relaxed text-muted-foreground">
                                    Stone Logistic Platform centraliza el
                                    trabajo diario de Maed Logistic Trading:
                                    órdenes, almacén, tracking por eventos,
                                    alertas e indicadores operativos en una sola
                                    interfaz.
                                </p>

                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                                    {!auth.user ? (
                                        <Link
                                            href={login()}
                                            className="hover-lift transition-smooth inline-flex items-center justify-center gap-2 rounded-xl border-2 border-border bg-card px-8 py-4 font-bold text-foreground hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                        >
                                            Iniciar sesión
                                            <ArrowRight className="h-5 w-5" />
                                        </Link>
                                    ) : (
                                        <Link
                                            href={dashboard()}
                                            className="gradient-primary hover-lift group shadow-premium-lg transition-smooth hover:shadow-premium-xl relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl px-8 py-4 font-bold text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                        >
                                            <span className="relative z-10">
                                                Ir al Dashboard
                                            </span>
                                            <ArrowRight className="relative z-10 h-5 w-5 transition-transform group-hover:translate-x-1" />
                                        </Link>
                                    )}
                                </div>

                                {/* Capabilities instead of “marketing stats” */}
                                <div className="grid gap-3 sm:grid-cols-2">
                                    {capabilities.map((c) => (
                                        <div
                                            key={c.title}
                                            className="glass-strong shadow-premium-xs rounded-xl border border-border/60 p-4"
                                        >
                                            <div className="text-sm font-extrabold text-foreground">
                                                {c.title}
                                            </div>
                                            <div className="mt-1 text-sm text-muted-foreground">
                                                {c.desc}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Operational Dashboard Preview */}
                            <div className="motion-safe:animate-fade-in-up relative motion-safe:delay-200 motion-reduce:animate-none">
                                <div className="glass shadow-premium-lg relative rounded-2xl border border-border/50 p-6 lg:p-8">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <div className="text-sm font-semibold text-muted-foreground">
                                                Dashboard Operativo (vista
                                                previa)
                                            </div>
                                            <div className="mt-1 text-xl font-extrabold text-foreground">
                                                Operación de hoy
                                            </div>
                                        </div>
                                        <div className="rounded-lg border border-border bg-card px-3 py-2 text-xs font-semibold text-muted-foreground">
                                            Actualizado hace 2 min
                                        </div>
                                    </div>

                                    {/* KPI Row */}
                                    <div className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                                        <KpiCard
                                            label="Recibidos"
                                            value="18"
                                            tone="primary"
                                        />
                                        <KpiCard
                                            label="En almacén"
                                            value="42"
                                            tone="accent"
                                        />
                                        <KpiCard
                                            label="En ruta"
                                            value="27"
                                            tone="info"
                                        />
                                        <KpiCard
                                            label="Entregados"
                                            value="31"
                                            tone="success"
                                        />
                                    </div>

                                    {/* Alerts + Queue */}
                                    <div className="mt-6 grid gap-4 lg:grid-cols-2">
                                        <div className="glass-strong rounded-xl border border-border/60 p-5">
                                            <div className="flex items-center justify-between">
                                                <div className="text-sm font-extrabold text-foreground">
                                                    Alertas
                                                </div>
                                                <span className="bg-warning/20 rounded-full px-2 py-0.5 text-xs font-bold text-foreground">
                                                    3
                                                </span>
                                            </div>

                                            <ul className="mt-3 space-y-3 text-sm">
                                                <li className="flex items-start gap-3">
                                                    <span className="bg-warning mt-1 h-2 w-2 rounded-full" />
                                                    <div>
                                                        <div className="font-semibold text-foreground">
                                                            Documentos faltantes
                                                        </div>
                                                        <div className="text-muted-foreground">
                                                            2 envíos requieren
                                                            verificación antes
                                                            de despacho.
                                                        </div>
                                                    </div>
                                                </li>

                                                <li className="flex items-start gap-3">
                                                    <span className="mt-1 h-2 w-2 rounded-full bg-destructive" />
                                                    <div>
                                                        <div className="font-semibold text-foreground">
                                                            Incidencia en ruta
                                                        </div>
                                                        <div className="text-muted-foreground">
                                                            1 entrega marcada
                                                            como “retenida”.
                                                        </div>
                                                    </div>
                                                </li>

                                                <li className="flex items-start gap-3">
                                                    <span className="bg-info mt-1 h-2 w-2 rounded-full" />
                                                    <div>
                                                        <div className="font-semibold text-foreground">
                                                            SLA próximo a vencer
                                                        </div>
                                                        <div className="text-muted-foreground">
                                                            3 envíos con
                                                            prioridad alta hoy.
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>

                                        <div className="glass-strong rounded-xl border border-border/60 p-5">
                                            <div className="text-sm font-extrabold text-foreground">
                                                Cola de despacho
                                            </div>
                                            <div className="mt-3 space-y-3">
                                                <QueueRow
                                                    code="MLT-02491"
                                                    status="Listo para salida"
                                                    meta="2 bultos • SDQ → STI"
                                                />
                                                <QueueRow
                                                    code="MLT-02488"
                                                    status="En verificación"
                                                    meta="1 caja • MIA → SDQ"
                                                />
                                                <QueueRow
                                                    code="MLT-02477"
                                                    status="Pendiente de pago"
                                                    meta="3 bultos • SDQ → PUJ"
                                                />
                                            </div>

                                            <div className="mt-5 flex items-center gap-3">
                                                <button
                                                    type="button"
                                                    className="hover-lift transition-smooth inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-2 text-sm font-semibold text-foreground hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                                >
                                                    Ver todo
                                                </button>
                                                <button
                                                    type="button"
                                                    className="gradient-primary hover-lift transition-smooth inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                                >
                                                    Crear orden
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="absolute -top-4 -right-4 h-24 w-24 rounded-full bg-primary/20 opacity-50 blur-2xl" />
                                    <div className="absolute -bottom-4 -left-4 h-32 w-32 rounded-full bg-accent/20 opacity-50 blur-2xl" />
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Features */}
                <section className="relative z-10 px-6 py-14 lg:px-8 lg:py-20">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-12 text-center">
                            <h2 className="mb-4 text-4xl font-extrabold tracking-tight text-foreground sm:text-5xl">
                                Módulos enfocados en{' '}
                                <span className="bg-gradient-to-r from-primary to-accent bg-clip-text text-transparent">
                                    operación logística real
                                </span>
                            </h2>
                            <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                                Diseñado para el día a día de Maed Logistic
                                Trading: trazabilidad, control y visibilidad
                                operativa.
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature, index) => (
                                <div
                                    key={feature.title}
                                    className="hover-lift glass group transition-smooth hover:shadow-premium-lg motion-safe:animate-fade-in-up relative overflow-hidden rounded-2xl border-2 border-border/50 p-8 hover:border-primary/30 motion-reduce:animate-none"
                                    style={{
                                        animationDelay: `${index * 90}ms`,
                                    }}
                                >
                                    <div
                                        className={`absolute inset-0 bg-gradient-to-br ${feature.accent.glowFrom} to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100`}
                                    />
                                    <div className="relative z-10">
                                        <div
                                            className={`mb-4 inline-flex h-14 w-14 items-center justify-center rounded-xl border ${feature.accent.iconBorder} ${feature.accent.iconBg} shadow-premium-md transition-transform duration-500 group-hover:scale-110 group-hover:rotate-2`}
                                        >
                                            <feature.icon
                                                className={`h-7 w-7 ${feature.accent.iconText}`}
                                            />
                                        </div>

                                        <h3 className="mb-3 text-xl font-extrabold text-foreground">
                                            {feature.title}
                                        </h3>
                                        <p className="text-muted-foreground">
                                            {feature.description}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA */}
                <section className="relative z-10 px-6 py-14 lg:px-8 lg:py-20">
                    <div className="mx-auto max-w-7xl">
                        <div className="gradient-primary shadow-premium-xl relative overflow-hidden rounded-3xl p-12 lg:p-16">
                            <div className="absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.2)_1px,transparent_0)] [background-size:28px_28px] opacity-25" />

                            <div className="relative z-10 text-center">
                                <h2 className="mb-4 text-4xl font-extrabold text-primary-foreground sm:text-5xl">
                                    Acceso operativo
                                </h2>
                                <p className="mb-8 text-xl text-primary-foreground/90">
                                    Ingresa para gestionar la operación y
                                    monitorear el estado de los envíos en tiempo
                                    real.
                                </p>

                                <div className="flex flex-col gap-4 sm:flex-row sm:justify-center">
                                    {!auth.user ? (
                                        <Link
                                            href={login()}
                                            className="hover-lift transition-smooth inline-flex items-center justify-center gap-2 rounded-xl border-2 border-primary-foreground/30 bg-primary-foreground/10 px-8 py-4 font-bold text-primary-foreground backdrop-blur-sm hover:bg-primary-foreground/20 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-primary focus-visible:outline-none"
                                        >
                                            Iniciar sesión
                                            <ArrowRight className="h-5 w-5" />
                                        </Link>
                                    ) : (
                                        <Link
                                            href={dashboard()}
                                            className="hover-lift group shadow-premium-xl transition-smooth inline-flex items-center justify-center gap-2 rounded-xl bg-card px-8 py-4 font-bold text-foreground hover:scale-[1.02] focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-primary focus-visible:outline-none"
                                        >
                                            Ir al Dashboard
                                            <ArrowRight className="h-5 w-5 transition-transform group-hover:translate-x-1" />
                                        </Link>
                                    )}
                                </div>

                                <div className="mt-6 text-sm text-primary-foreground/80">
                                    ¿Problemas de acceso? Contacta al
                                    administrador del sistema.
                                </div>
                            </div>

                            <div className="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-primary-foreground/10 blur-3xl" />
                            <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-accent/20 blur-3xl" />
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="glass-strong shadow-premium-sm relative z-10 border-t border-border">
                    <div className="mx-auto max-w-7xl px-6 py-12 lg:px-8">
                        <div className="grid gap-8 lg:grid-cols-3">
                            <div>
                                <div className="flex items-center space-x-3">
                                    <div>
                                        <AppLogoIcon />
                                    </div>
                                    <div className="leading-tight">
                                        <div className="text-xl font-extrabold text-foreground">
                                            Cafe Santoral
                                        </div>
                                        <div className="text-xs font-medium text-muted-foreground">
                                            Cima alta platform
                                        </div>
                                    </div>
                                </div>
                                <p className="mt-4 text-sm text-muted-foreground">
                                    Plataforma operativa para gestionar la
                                    logística con control, trazabilidad y
                                    visibilidad.
                                </p>
                            </div>

                            <div>
                                <h3 className="mb-4 text-sm font-semibold tracking-wider text-foreground uppercase">
                                    Módulos
                                </h3>
                                <ul className="space-y-2 text-sm text-muted-foreground">
                                    <li className="flex items-center gap-2">
                                        <CheckCircle className="h-4 w-4 text-primary" />
                                        Órdenes y operación
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <CheckCircle className="h-4 w-4 text-primary" />
                                        Tracking y entregas
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <CheckCircle className="h-4 w-4 text-primary" />
                                        Almacén e inventario
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <CheckCircle className="h-4 w-4 text-primary" />
                                        Reportes operativos
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <h3 className="mb-4 text-sm font-semibold tracking-wider text-foreground uppercase">
                                    Empresa
                                </h3>
                                <ul className="space-y-2 text-sm text-muted-foreground">
                                    <li>Soporte</li>
                                    <li>Contacto</li>
                                    <li>Términos</li>
                                    <li>Privacidad</li>
                                </ul>
                            </div>
                        </div>

                        <div className="mt-8 border-t border-border pt-8 text-center text-sm text-muted-foreground">
                            <p>
                                © {new Date().getFullYear()} Stone Logistic
                                Platform. Todos los derechos reservados.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

function toneClasses(tone: 'primary' | 'accent' | 'info' | 'success') {
    switch (tone) {
        case 'primary':
            return {
                badge: 'bg-primary/10 text-primary border-primary/20',
                value: 'text-primary',
            };
        case 'accent':
            return {
                badge: 'bg-accent/10 text-accent border-accent/20',
                value: 'text-accent',
            };
        case 'info':
            return {
                badge: 'bg-info/15 text-foreground border-info/25',
                value: 'text-foreground',
            };
        case 'success':
            return {
                badge: 'bg-success/15 text-foreground border-success/25',
                value: 'text-foreground',
            };
    }
}

function KpiCard(props: {
    label: string;
    value: string;
    tone: 'primary' | 'accent' | 'info' | 'success';
}) {
    const t = toneClasses(props.tone);

    return (
        <div className="hover-lift glass-strong transition-smooth rounded-xl border border-border/60 p-4 hover:border-primary/30">
            <div
                className={`inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-bold ${t.badge}`}
            >
                {props.label}
            </div>
            <div className={`mt-2 text-3xl font-extrabold ${t.value}`}>
                {props.value}
            </div>
            <div className="mt-1 text-xs text-muted-foreground">Hoy</div>
        </div>
    );
}

function QueueRow(props: { code: string; status: string; meta: string }) {
    return (
        <div className="flex items-start justify-between gap-3 rounded-lg border border-border/60 bg-card/50 p-3">
            <div>
                <div className="text-sm font-extrabold text-foreground">
                    {props.code}
                </div>
                <div className="mt-0.5 text-sm text-muted-foreground">
                    {props.meta}
                </div>
            </div>
            <div className="text-right">
                <div className="text-xs font-semibold text-foreground">
                    {props.status}
                </div>
                <div className="mt-1 text-xs text-muted-foreground">
                    Ver detalles
                </div>
            </div>
        </div>
    );
}
