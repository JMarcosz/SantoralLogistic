import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import dgii from '@/routes/dgii'; // Helpers de rutas generados automáticamente a partir de los controladores Laravel.
// Estos helpers ya están enlazados con endpoints
// Se usan aquí para mantener la conexión directa con el backend sin necesidad de hardcodear URLs.

import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { FileDown } from 'lucide-react';
import { useState } from 'react';

// Breadcrumbs para navegación
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Exportaciones DGII', href: dgii.export[607].url() },
];

export default function Exports() {
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 5 }, (_, i) => currentYear - i);
    const months = Array.from({ length: 12 }, (_, i) =>
        String(i + 1).padStart(2, '0'),
    );

    const [year, setYear] = useState('');
    const [month, setMonth] = useState('');

    const period = year && month ? `${year}-${month}` : '';

    const handleDownload = (type: '607' | '608') => {
        if (!period) return;

        // Aquí se construye la URL usando los helpers de rutas generados.
        // La query "period" se pasa como parámetro para que el backend reciba el año-mes seleccionado.

        const url =
            type === '607'
                ? dgii.export[607].url({ query: { period } })
                : dgii.export[608].url({ query: { period } });

        // Por ahora solo se simula la descarga con console.log.
        // Cuando el backend implemente los endpoints en DgiiExportController,
        // se reemplazará por window.open(url, '_blank') o route(...) para iniciar la descarga real.

        console.log('Descargando (simulación):', url);
        // window.open(url, '_blank'); // cuando el backend esté listo
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Exportaciones DGII" />

            <div className="container mx-auto space-y-8 px-4 py-8">
                {/* Header Card */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute bottom-0 left-0 h-32 w-32 -translate-x-10 translate-y-10 rounded-full bg-primary/5 blur-2xl" />

                    <div className="relative flex items-start gap-6">
                        {/* Icon Circle */}
                        <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                            <FileDown className="h-10 w-10 text-primary-foreground" />
                        </div>

                        {/* Title and Description */}
                        <div className="space-y-2">
                            <h1 className="text-4xl font-bold tracking-tight">
                                Exportaciones DGII
                            </h1>
                            <p className="text-lg text-muted-foreground">
                                Descarga los archivos 607 (ventas) y 608
                                (anulados) para subir a la DGII.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Selector de período */}
                <div className="flex gap-4">
                    <Select onValueChange={setYear}>
                        <SelectTrigger className="w-[120px]">
                            <SelectValue placeholder="Año" />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map((y) => (
                                <SelectItem key={y} value={String(y)}>
                                    {y}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select onValueChange={setMonth}>
                        <SelectTrigger className="w-[120px]">
                            <SelectValue placeholder="Mes" />
                        </SelectTrigger>
                        <SelectContent>
                            {months.map((m) => (
                                <SelectItem key={m} value={m}>
                                    {m}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Botones de acción */}
                <div className="flex gap-4">
                    <Button
                        onClick={() => handleDownload('607')}
                        disabled={!period}
                        className="bg-blue-600 text-white"
                    >
                        Descargar 607
                    </Button>
                    <Button
                        onClick={() => handleDownload('608')}
                        disabled={!period}
                        className="bg-purple-600 text-white"
                    >
                        Descargar 608
                    </Button>
                </div>

                {/* Mensaje de validación */}
                {!period && (
                    <p className="mt-4 text-sm text-red-500">
                        Selecciona un año y un mes antes de descargar.
                    </p>
                )}
            </div>
        </AppLayout>
    );
}
