import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { router } from '@inertiajs/react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { Calendar, Lock, LockOpen } from 'lucide-react';
import { useState } from 'react';

interface User {
    id: number;
    name: string;
}

interface Period {
    id: number;
    year: number;
    month: number;
    status: 'open' | 'closed';
    lock_date: string | null;
    closed_at: string | null;
    closed_by: number | null;
    reopened_at: string | null;
    reopened_by: number | null;
    period_name: string;
    display_name: string;
    closer?: User;
    reopener?: User;
}

interface Props {
    periods: Period[];
    currentYear: number;
    canClosePeriod: boolean;
}

export default function PeriodsIndex({
    periods,
    currentYear,
    canClosePeriod,
}: Props) {
    const [selectedYear, setSelectedYear] = useState(currentYear);

    const handleYearChange = (year: string) => {
        const yearNum = parseInt(year);
        setSelectedYear(yearNum);
        router.get('/accounting/periods', { year: yearNum });
    };

    const handleClose = (period: Period) => {
        if (
            confirm(
                `¿Cerrar el período ${period.display_name}?\n\nNo se podrán crear asientos en este período una vez cerrado.`,
            )
        ) {
            router.post(
                `/accounting/periods/${period.id}/close`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload({ only: ['periods'] });
                    },
                },
            );
        }
    };

    const handleReopen = (period: Period) => {
        if (
            confirm(
                `¿Reabrir el período ${period.display_name}?\n\nEsta acción permite nuevamente crear asientos en este período.`,
            )
        ) {
            router.post(
                `/accounting/periods/${period.id}/reopen`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload({ only: ['periods'] });
                    },
                },
            );
        }
    };

    const getStatusBadge = (period: Period) => {
        if (period.status === 'open') {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-950 dark:text-green-300">
                    <LockOpen className="h-4 w-4" />
                    Abierto
                </span>
            );
        } else {
            return (
                <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-950 dark:text-red-300">
                    <Lock className="h-4 w-4" />
                    Cerrado
                </span>
            );
        }
    };

    // Generate year options (current year ± 5)
    const yearOptions = [];
    for (let y = currentYear - 5; y <= currentYear + 5; y++) {
        yearOptions.push(y);
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Contabilidad', href: '/accounting' },
                { title: 'Períodos Contables', href: '/accounting/periods' },
            ]}
        >
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-500/10">
                            <Calendar className="h-6 w-6 text-purple-600" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold">
                                Períodos Contables
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Gestión de períodos para control de asientos
                            </p>
                        </div>
                    </div>

                    {/* Year Selector */}
                    <div className="flex items-center gap-3">
                        <label className="text-sm font-medium">
                            Año Fiscal:
                        </label>
                        <Select
                            value={selectedYear.toString()}
                            onValueChange={handleYearChange}
                        >
                            <SelectTrigger className="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {yearOptions.map((year) => (
                                    <SelectItem
                                        key={year}
                                        value={year.toString()}
                                    >
                                        {year}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Periods Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Períodos del Año {selectedYear}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="p-3 text-left font-semibold">
                                            Mes
                                        </th>
                                        <th className="p-3 text-center font-semibold">
                                            Estado
                                        </th>
                                        <th className="p-3 text-left font-semibold">
                                            Cerrado Por
                                        </th>
                                        <th className="p-3 text-left font-semibold">
                                            Fecha Cierre
                                        </th>
                                        {canClosePeriod && (
                                            <th className="p-3 text-center font-semibold">
                                                Acciones
                                            </th>
                                        )}
                                    </tr>
                                </thead>
                                <tbody>
                                    {periods.map((period) => (
                                        <tr
                                            key={period.id}
                                            className="border-b transition-colors hover:bg-muted/50"
                                        >
                                            <td className="p-3 font-medium">
                                                {period.display_name}
                                            </td>
                                            <td className="p-3 text-center">
                                                {getStatusBadge(period)}
                                            </td>
                                            <td className="p-3 text-sm text-muted-foreground">
                                                {period.closer?.name || '-'}
                                            </td>
                                            <td className="p-3 text-sm text-muted-foreground">
                                                {period.closed_at
                                                    ? format(
                                                          new Date(
                                                              period.closed_at,
                                                          ),
                                                          'dd/MM/yyyy HH:mm',
                                                          { locale: es },
                                                      )
                                                    : '-'}
                                            </td>
                                            {canClosePeriod && (
                                                <td className="p-3 text-center">
                                                    {period.status ===
                                                    'open' ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleClose(
                                                                    period,
                                                                )
                                                            }
                                                            className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                        >
                                                            <Lock className="mr-1 h-4 w-4" />
                                                            Cerrar
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() =>
                                                                handleReopen(
                                                                    period,
                                                                )
                                                            }
                                                            className="text-green-600 hover:bg-green-50 hover:text-green-700"
                                                        >
                                                            <LockOpen className="mr-1 h-4 w-4" />
                                                            Reabrir
                                                        </Button>
                                                    )}
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                {/* Info Card */}
                <Card className="border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/20">
                    <CardHeader>
                        <CardTitle className="text-blue-900 dark:text-blue-100">
                            ℹ️ Información sobre Períodos Contables
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-blue-800 dark:text-blue-200">
                        <ul className="space-y-2">
                            <li>
                                • <strong>Período Abierto:</strong> Se pueden
                                crear y postear asientos contables normalmente.
                            </li>
                            <li>
                                • <strong>Período Cerrado:</strong> No se
                                permite crear asientos. Utilizado para cierre
                                mensual/anual.
                            </li>
                            <li>
                                • <strong>Reapertura:</strong> Solo
                                administradores pueden reabrir períodos
                                cerrados.
                            </li>
                            <li>
                                • <strong>Auditoría:</strong> Todos los cierres
                                y reaperturas quedan registrados con usuario y
                                fecha.
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
