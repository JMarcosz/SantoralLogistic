import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    BookOpen,
    Calculator,
    FileSpreadsheet,
    Lock,
    TrendingUp,
} from 'lucide-react';

interface Props {
    can: {
        manage: boolean;
        post: boolean;
        closePeriod: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
];

export default function AccountingIndex({ can }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Contabilidad" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500/10">
                            <Calculator className="h-6 w-6 text-blue-600" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold">
                                Módulo de Contabilidad
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Sistema de contabilidad general y doble partida
                            </p>
                        </div>
                    </div>
                </div>

                {/* Coming Soon Message */}
                <Card className="border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/20">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-blue-900 dark:text-blue-100">
                            <TrendingUp className="h-5 w-5" />
                            Módulo en Desarrollo
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-blue-800 dark:text-blue-200">
                        <p className="mb-4">
                            El módulo de Contabilidad está actualmente en fase
                            de implementación. Funcionalidades planificadas:
                        </p>
                        <ul className="space-y-2">
                            <li className="flex items-start gap-2">
                                <BookOpen className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                <span>
                                    <strong>Plan de Cuentas (COA)</strong> -
                                    Gestión jerárquica de cuentas contables
                                </span>
                            </li>
                            <li className="flex items-start gap-2">
                                <FileSpreadsheet className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                <span>
                                    <strong>Asientos Contables</strong> - Doble
                                    partida manual y automática
                                </span>
                            </li>
                            <li className="flex items-start gap-2">
                                <Lock className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                <span>
                                    <strong>Períodos Contables</strong> -
                                    Apertura, cierre y bloqueo de períodos
                                </span>
                            </li>
                            <li className="flex items-start gap-2">
                                <TrendingUp className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                <span>
                                    <strong>Reportes</strong> - Mayor general,
                                    balance de comprobación
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                {/* Permissions Status */}
                <Card>
                    <CardHeader>
                        <CardTitle>Permisos del Usuario</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                className={`rounded-lg border p-4 ${
                                    can.manage
                                        ? 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/20'
                                        : 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/20'
                                }`}
                            >
                                <div className="flex items-center gap-2">
                                    <div
                                        className={`h-2 w-2 rounded-full ${
                                            can.manage
                                                ? 'bg-green-500'
                                                : 'bg-gray-400'
                                        }`}
                                    />
                                    <span className="font-medium">
                                        Administrar
                                    </span>
                                </div>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Gestionar cuentas y configuración
                                </p>
                            </div>

                            <div
                                className={`rounded-lg border p-4 ${
                                    can.post
                                        ? 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/20'
                                        : 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/20'
                                }`}
                            >
                                <div className="flex items-center gap-2">
                                    <div
                                        className={`h-2 w-2 rounded-full ${
                                            can.post
                                                ? 'bg-green-500'
                                                : 'bg-gray-400'
                                        }`}
                                    />
                                    <span className="font-medium">
                                        Postear Asientos
                                    </span>
                                </div>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Aprobar y finalizar asientos contables
                                </p>
                            </div>

                            <div
                                className={`rounded-lg border p-4 ${
                                    can.closePeriod
                                        ? 'border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950/20'
                                        : 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/20'
                                }`}
                            >
                                <div className="flex items-center gap-2">
                                    <div
                                        className={`h-2 w-2 rounded-full ${
                                            can.closePeriod
                                                ? 'bg-green-500'
                                                : 'bg-gray-400'
                                        }`}
                                    />
                                    <span className="font-medium">
                                        Cerrar Períodos
                                    </span>
                                </div>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Bloquear períodos contables
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Development Timeline */}
                <Card>
                    <CardHeader>
                        <CardTitle>Progreso de Implementación</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div>
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="text-sm font-medium">
                                        ACC-0: Fundaciones
                                    </span>
                                    <span className="text-sm font-medium text-green-600">
                                        ✓ Completado
                                    </span>
                                </div>
                                <div className="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-800">
                                    <div className="h-2 w-full rounded-full bg-green-500" />
                                </div>
                            </div>

                            <div>
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="text-sm font-medium">
                                        ACC-001: Database Schema
                                    </span>
                                    <span className="text-sm text-gray-500">
                                        Pendiente
                                    </span>
                                </div>
                                <div className="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-800">
                                    <div className="h-2 w-0 rounded-full bg-blue-500" />
                                </div>
                            </div>

                            <div>
                                <div className="mb-2 flex items-center justify-between">
                                    <span className="text-sm font-medium">
                                        Módulo Completo
                                    </span>
                                    <span className="text-sm text-gray-500">
                                        8%
                                    </span>
                                </div>
                                <div className="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-800">
                                    <div className="h-2 w-[8%] rounded-full bg-blue-500" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
