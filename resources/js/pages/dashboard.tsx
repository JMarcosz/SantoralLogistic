import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import customers from '@/routes/customers';
import shippingOrders from '@/routes/shipping-orders';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    ClipboardList,
    FileText,
    type LucideIcon,
    Package,
    Route as RouteIcon,
    Truck,
    Users,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

interface Kpi {
    label: string;
    value: string;
    tone: 'primary' | 'accent' | 'info' | 'success' | 'warning' | 'neutral';
    icon: string;
}

interface Alert {
    title: string;
    detail: string;
    severity: 'warning' | 'info' | 'destructive';
}

interface QueueItem {
    code: string;
    status: string;
    meta: string;
}

interface DashboardProps {
    kpis: Kpi[];
    alerts: Alert[];
    workQueue: QueueItem[];
}

// Icon mapping helper
const getIcon = (name: string) => {
    const icons: Record<string, LucideIcon> = {
        Package,
        Truck,
        CheckCircle2,
        AlertTriangle,
        Activity,
        ClipboardList,
        RouteIcon,
        FileText,
        Users,
    };
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    return icons[name] || (Package as any);
};

export default function Dashboard({
    kpis = [],
    alerts = [],
    workQueue = [],
}: DashboardProps) {
    const quickActions = [
        {
            title: 'Crear orden',
            description: 'Registrar una nueva orden de envío',
            icon: ClipboardList,
            href: shippingOrders.create().url,
        },
        {
            title: 'Registrar recepción',
            description: 'Entrada de carga a almacén',
            icon: Package,
            href: '#', // TODO: Add route
        },
        {
            title: 'Despachar / asignar ruta',
            description: 'Preparar salida y asignación',
            icon: RouteIcon,
            href: '#', // TODO: Add route
        },
        {
            title: 'Registrar entrega',
            description: 'Marcar entrega y evidencia',
            icon: CheckCircle2,
            href: '#', // TODO: Add route
        },
        {
            title: 'Tracking',
            description: 'Buscar por código y ver eventos',
            icon: Truck,
            href: '#', // TODO: Add route
        },
        {
            title: 'Clientes',
            description: 'Gestión de clientes y contactos',
            icon: Users,
            href: customers.index().url,
        },
        {
            title: 'Documentos',
            description: 'Manifiestos, facturas, anexos',
            icon: FileText,
            href: '#', // TODO: Add route
        },
    ] as const;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Header */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-primary/5 blur-2xl" />

                    <div className="relative flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div className="flex items-start gap-6">
                            <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                                <ClipboardList className="h-8 w-8 text-primary-foreground" />
                            </div>

                            <div className="space-y-1">
                                <h1 className="text-4xl font-extrabold tracking-tight">
                                    Dashboard Operativo
                                </h1>
                                <p className="text-lg text-muted-foreground">
                                    Visibilidad de operación, alertas y accesos
                                    rápidos.
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Maed Logistic Trading · Stone Logistic
                                    Platform
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Link
                                href="#"
                                className="hover-lift transition-smooth inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                Ver tracking
                                <ArrowRight className="h-4 w-4" />
                            </Link>

                            <Link
                                href="#"
                                className="gradient-primary hover-lift transition-smooth inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                Crear orden
                                <ArrowRight className="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Quick Actions */}
                <section className="space-y-3">
                    <div className="flex items-end justify-between gap-4">
                        <div>
                            <h2 className="text-lg font-extrabold text-foreground">
                                Accesos rápidos
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Acciones comunes de la operación.
                            </p>
                        </div>
                        <span className="text-xs text-muted-foreground">
                            Personalizable por rol
                        </span>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {quickActions.map((a) => (
                            <Link
                                key={a.title}
                                href={a.href}
                                className="hover-lift transition-smooth group shadow-premium-sm hover:shadow-premium-md rounded-2xl border border-border/60 bg-card/70 p-5 hover:border-primary/30 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <div className="text-sm font-extrabold text-foreground">
                                            {a.title}
                                        </div>
                                        <div className="mt-1 text-sm text-muted-foreground">
                                            {a.description}
                                        </div>
                                    </div>
                                    <div className="rounded-xl bg-primary/10 p-2">
                                        <a.icon className="h-5 w-5 text-primary" />
                                    </div>
                                </div>

                                <div className="mt-4 flex items-center gap-2 text-xs font-semibold text-primary">
                                    Abrir
                                    <ArrowRight className="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>

                <section className="space-y-3">
                    <div>
                        <h2 className="text-lg font-extrabold text-foreground">
                            Estado de operación
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Indicadores clave por estado.
                        </p>
                    </div>

                    <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                        {kpis.map((k) => (
                            <KpiTile
                                key={k.label}
                                {...k}
                                icon={getIcon(k.icon)}
                            />
                        ))}
                    </div>
                </section>

                {/* Alerts + Work Queue */}
                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="shadow-premium-md hover:shadow-premium-lg transition-smooth rounded-2xl border border-border/60 bg-card/70 p-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-extrabold text-foreground">
                                Alertas
                            </h3>
                            <span className="bg-warning/20 rounded-full px-2 py-0.5 text-xs font-bold text-foreground">
                                {alerts.length}
                            </span>
                        </div>

                        <div className="mt-4 space-y-3">
                            {alerts.map((a) => (
                                <AlertRow key={a.title} {...a} />
                            ))}
                        </div>
                    </div>

                    <div className="shadow-premium-md hover:shadow-premium-lg transition-smooth rounded-2xl border border-border/60 bg-card/70 p-6">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-extrabold text-foreground">
                                Cola de trabajo
                            </h3>
                            <Link
                                href="#"
                                className="text-sm font-semibold text-primary hover:underline"
                            >
                                Ver todo
                            </Link>
                        </div>

                        <div className="mt-4 space-y-3">
                            {workQueue.map((r) => (
                                <QueueRow key={r.code} {...r} />
                            ))}
                        </div>
                    </div>
                </section>

                {/* Analytics (optional placeholders) */}
                <section className="grid gap-6 md:grid-cols-2">
                    <div className="shadow-premium-md hover:shadow-premium-lg transition-smooth rounded-2xl border border-border/60 bg-card/70 p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-extrabold text-foreground">
                                    Inventario
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Resumen por almacén
                                </p>
                            </div>
                            <div className="rounded-lg bg-primary/10 p-2">
                                <Package className="h-5 w-5 text-primary" />
                            </div>
                        </div>
                        <div className="relative mt-4 aspect-video overflow-hidden rounded-lg border border-border/50">
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        </div>
                    </div>

                    <div className="shadow-premium-md hover:shadow-premium-lg transition-smooth rounded-2xl border border-border/60 bg-card/70 p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-extrabold text-foreground">
                                    Finanzas
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Facturación y cobros
                                </p>
                            </div>
                            <div className="rounded-lg bg-primary/10 p-2">
                                <Activity className="h-5 w-5 text-primary" />
                            </div>
                        </div>
                        <div className="relative mt-4 aspect-video overflow-hidden rounded-lg border border-border/50">
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        </div>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

function KpiTile({
    label,
    value,
    tone,
    icon: Icon,
}: {
    label: string;
    value: string;
    tone: 'primary' | 'accent' | 'info' | 'success' | 'warning' | 'neutral';
    icon: React.ComponentType<{ className?: string }>;
}) {
    const toneClass = (() => {
        switch (tone) {
            case 'primary':
                return 'bg-primary/10 text-primary border-primary/20';
            case 'accent':
                return 'bg-accent/10 text-accent border-accent/20';
            case 'info':
                return 'bg-info/15 text-foreground border-info/25';
            case 'success':
                return 'bg-success/15 text-foreground border-success/25';
            case 'warning':
                return 'bg-warning/15 text-foreground border-warning/25';
            default:
                return 'bg-muted text-foreground border-border';
        }
    })();

    return (
        <div className="hover-lift transition-smooth shadow-premium-sm hover:shadow-premium-md rounded-2xl border border-border/60 bg-card/70 p-4 hover:border-primary/30">
            <div className="flex items-center justify-between">
                <span
                    className={`inline-flex items-center gap-2 rounded-full border px-2 py-0.5 text-xs font-bold ${toneClass}`}
                >
                    <Icon className="h-3.5 w-3.5" />
                    {label}
                </span>
            </div>

            <div className="mt-3 text-3xl font-extrabold text-foreground">
                {value}
            </div>
            <div className="mt-1 text-xs text-muted-foreground">Hoy</div>
        </div>
    );
}

function AlertRow({
    title,
    detail,
    severity,
}: {
    title: string;
    detail: string;
    severity: 'warning' | 'info' | 'destructive';
}) {
    const dot =
        severity === 'destructive'
            ? 'bg-destructive'
            : severity === 'warning'
              ? 'bg-warning'
              : 'bg-info';

    return (
        <div className="flex items-start gap-3 rounded-xl border border-border/60 bg-card/60 p-4">
            <span className={`mt-1.5 h-2 w-2 rounded-full ${dot}`} />
            <div>
                <div className="text-sm font-extrabold text-foreground">
                    {title}
                </div>
                <div className="mt-1 text-sm text-muted-foreground">
                    {detail}
                </div>
            </div>
        </div>
    );
}

function QueueRow({
    code,
    status,
    meta,
}: {
    code: string;
    status: string;
    meta: string;
}) {
    return (
        <div className="flex items-start justify-between gap-3 rounded-xl border border-border/60 bg-card/60 p-4">
            <div>
                <div className="text-sm font-extrabold text-foreground">
                    {code}
                </div>
                <div className="mt-1 text-sm text-muted-foreground">{meta}</div>
            </div>
            <div className="text-right">
                <div className="text-xs font-semibold text-foreground">
                    {status}
                </div>
                <div className="mt-1 text-xs text-primary">Ver</div>
            </div>
        </div>
    );
}
