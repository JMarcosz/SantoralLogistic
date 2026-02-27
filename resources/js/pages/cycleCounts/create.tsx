/* eslint-disable @typescript-eslint/no-unused-vars */
import { Badge } from '@/components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import cycleCountRoutes from '@/routes/cycle-counts';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    ClipboardList,
    Filter,
    Info,
    Loader2,
    Package,
    Save,
} from 'lucide-react';
import { useState } from 'react';

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface Customer {
    id: number;
    name: string;
    code: string;
}

interface PageProps {
    warehouses: Warehouse[];
    customers: Customer[];
}

export default function CycleCountCreate({ warehouses, customers }: PageProps) {
    const [step, setStep] = useState(1);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        warehouse_id: '',
        reference: '',
        scheduled_at: '',
        notes: '',
        filters: {
            customer_id: '',
            sku: '',
            location_id: '',
        },
    });

    const selectedWarehouse = warehouses.find(
        (w) => w.id === parseInt(data.warehouse_id),
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        post(cycleCountRoutes.store().url, {
            onError: () => setIsSubmitting(false),
            onSuccess: () => setIsSubmitting(false),
        });
    };

    const canProceed = data.warehouse_id !== '';

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Conteos Cíclicos',
                    href: cycleCountRoutes.index().url,
                },
                { title: 'Nuevo Conteo', href: cycleCountRoutes.create().url },
            ]}
        >
            <Head title="Nuevo Conteo Cíclico" />

            <div className="mx-auto max-w-4xl space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={cycleCountRoutes.index().url}>
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold">
                            <ClipboardList className="h-6 w-6" />
                            Nuevo Conteo Cíclico
                        </h1>
                        <p className="text-muted-foreground">
                            Crear un nuevo conteo de inventario físico
                        </p>
                    </div>
                </div>

                {/* Progress Steps */}
                <div className="flex items-center justify-center gap-8">
                    <div
                        className={`flex items-center gap-2 ${step >= 1 ? 'text-primary' : 'text-muted-foreground'}`}
                    >
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full font-semibold ${
                                step >= 1
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted'
                            }`}
                        >
                            1
                        </div>
                        <span className="font-medium">Almacén</span>
                    </div>
                    <div className="h-0.5 w-16 bg-muted" />
                    <div
                        className={`flex items-center gap-2 ${step >= 2 ? 'text-primary' : 'text-muted-foreground'}`}
                    >
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full font-semibold ${
                                step >= 2
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted'
                            }`}
                        >
                            2
                        </div>
                        <span className="font-medium">Filtros</span>
                    </div>
                    <div className="h-0.5 w-16 bg-muted" />
                    <div
                        className={`flex items-center gap-2 ${step >= 3 ? 'text-primary' : 'text-muted-foreground'}`}
                    >
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full font-semibold ${
                                step >= 3
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted'
                            }`}
                        >
                            3
                        </div>
                        <span className="font-medium">Confirmar</span>
                    </div>
                </div>

                <form onSubmit={handleSubmit}>
                    {/* Step 1: Warehouse Selection */}
                    {step === 1 && (
                        <Card className="animate-in fade-in-50 slide-in-from-right-10">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Package className="h-5 w-5" />
                                    Seleccionar Almacén
                                </CardTitle>
                                <CardDescription>
                                    Elija el almacén donde se realizará el
                                    conteo cíclico
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {warehouses.map((w) => (
                                        <button
                                            key={w.id}
                                            type="button"
                                            onClick={() =>
                                                setData(
                                                    'warehouse_id',
                                                    String(w.id),
                                                )
                                            }
                                            className={`rounded-lg border-2 p-4 text-left transition-all hover:border-primary/50 hover:bg-muted/50 ${
                                                data.warehouse_id ===
                                                String(w.id)
                                                    ? 'border-primary bg-primary/10'
                                                    : 'border-muted'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between">
                                                <div>
                                                    <p className="font-semibold">
                                                        {w.name}
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {w.code}
                                                    </p>
                                                </div>
                                                {data.warehouse_id ===
                                                    String(w.id) && (
                                                    <CheckCircle2 className="h-5 w-5 text-primary" />
                                                )}
                                            </div>
                                        </button>
                                    ))}
                                </div>

                                {errors.warehouse_id && (
                                    <div className="flex items-center gap-2 text-sm text-destructive">
                                        <AlertCircle className="h-4 w-4" />
                                        {errors.warehouse_id}
                                    </div>
                                )}

                                <div className="flex justify-end">
                                    <Button
                                        type="button"
                                        onClick={() => setStep(2)}
                                        disabled={!canProceed}
                                    >
                                        Continuar
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Step 2: Filters */}
                    {step === 2 && (
                        <Card className="animate-in fade-in-50 slide-in-from-right-10">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Filter className="h-5 w-5" />
                                    Filtros de Inventario
                                </CardTitle>
                                <CardDescription>
                                    Opcionalmente filtre qué ítems incluir en el
                                    conteo. Si no aplica filtros, se incluirán
                                    todos los ítems con stock.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Info alert */}
                                <div className="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
                                    <Info className="mt-0.5 h-5 w-5 flex-shrink-0" />
                                    <div className="text-sm">
                                        <p className="font-medium">
                                            ¿Sin filtros?
                                        </p>
                                        <p className="mt-1 text-blue-700 dark:text-blue-300">
                                            Si deja todos los filtros vacíos, se
                                            cargarán todos los productos con
                                            stock en el almacén "
                                            {selectedWarehouse?.name}". Esta es
                                            la opción recomendada para un conteo
                                            completo.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-6 md:grid-cols-2">
                                    {/* Customer Filter */}
                                    <div className="space-y-2">
                                        <Label htmlFor="customer_id">
                                            Filtrar por Cliente
                                        </Label>
                                        <Select
                                            value={
                                                data.filters.customer_id || ''
                                            }
                                            onValueChange={(v: string) =>
                                                setData('filters', {
                                                    ...data.filters,
                                                    customer_id:
                                                        v === 'all' ? '' : v,
                                                })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todos los clientes" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">
                                                    Todos los clientes
                                                </SelectItem>
                                                {customers.map((c) => (
                                                    <SelectItem
                                                        key={c.id}
                                                        value={String(c.id)}
                                                    >
                                                        {c.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            Solo contar productos de un cliente
                                            específico
                                        </p>
                                    </div>

                                    {/* SKU Filter */}
                                    <div className="space-y-2">
                                        <Label htmlFor="sku">
                                            Filtrar por SKU (contiene)
                                        </Label>
                                        <Input
                                            id="sku"
                                            value={data.filters.sku || ''}
                                            onChange={(e) =>
                                                setData('filters', {
                                                    ...data.filters,
                                                    sku: e.target.value,
                                                })
                                            }
                                            placeholder="Ej: PROD-001"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Filtra productos cuyo SKU contenga
                                            este texto
                                        </p>
                                    </div>
                                </div>

                                {/* Active Filters Summary */}
                                {(data.filters.customer_id ||
                                    data.filters.sku) && (
                                    <div className="rounded-lg bg-muted/50 p-4">
                                        <p className="mb-2 text-sm font-medium">
                                            Filtros activos:
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {data.filters.customer_id && (
                                                <Badge variant="secondary">
                                                    Cliente:{' '}
                                                    {
                                                        customers.find(
                                                            (c) =>
                                                                c.id ===
                                                                parseInt(
                                                                    data.filters
                                                                        .customer_id,
                                                                ),
                                                        )?.name
                                                    }
                                                </Badge>
                                            )}
                                            {data.filters.sku && (
                                                <Badge variant="secondary">
                                                    SKU contiene: "
                                                    {data.filters.sku}"
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                )}

                                <div className="flex justify-between">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setStep(1)}
                                    >
                                        Atrás
                                    </Button>
                                    <Button
                                        type="button"
                                        onClick={() => setStep(3)}
                                    >
                                        Continuar
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Step 3: Confirm & Additional Info */}
                    {step === 3 && (
                        <Card className="animate-in fade-in-50 slide-in-from-right-10">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CheckCircle2 className="h-5 w-5" />
                                    Confirmar Conteo
                                </CardTitle>
                                <CardDescription>
                                    Revise la configuración y agregue
                                    información adicional
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Summary */}
                                <div className="rounded-lg border-2 border-dashed p-4">
                                    <p className="mb-3 text-sm font-medium text-muted-foreground">
                                        Resumen del conteo
                                    </p>
                                    <dl className="grid gap-3 text-sm md:grid-cols-2">
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Almacén
                                            </dt>
                                            <dd className="font-medium">
                                                {selectedWarehouse?.name} (
                                                {selectedWarehouse?.code})
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-muted-foreground">
                                                Alcance
                                            </dt>
                                            <dd className="font-medium">
                                                {data.filters.customer_id ||
                                                data.filters.sku
                                                    ? 'Filtrado'
                                                    : 'Todo el inventario'}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="reference">
                                            Referencia
                                        </Label>
                                        <Input
                                            id="reference"
                                            value={data.reference}
                                            onChange={(e) =>
                                                setData(
                                                    'reference',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Ej: Conteo Mensual Dic 2024"
                                        />
                                        {errors.reference && (
                                            <p className="text-sm text-destructive">
                                                {errors.reference}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="scheduled_at">
                                            Fecha Programada
                                        </Label>
                                        <Input
                                            id="scheduled_at"
                                            type="datetime-local"
                                            value={data.scheduled_at}
                                            onChange={(e) =>
                                                setData(
                                                    'scheduled_at',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        {errors.scheduled_at && (
                                            <p className="text-sm text-destructive">
                                                {errors.scheduled_at}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="notes">Notas</Label>
                                    <Textarea
                                        id="notes"
                                        value={data.notes}
                                        onChange={(e) =>
                                            setData('notes', e.target.value)
                                        }
                                        placeholder="Observaciones del conteo..."
                                        rows={3}
                                    />
                                    {errors.notes && (
                                        <p className="text-sm text-destructive">
                                            {errors.notes}
                                        </p>
                                    )}
                                </div>

                                <div className="flex justify-between">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setStep(2)}
                                    >
                                        Atrás
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing || isSubmitting}
                                        className="min-w-[140px]"
                                    >
                                        {processing || isSubmitting ? (
                                            <>
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                Creando...
                                            </>
                                        ) : (
                                            <>
                                                <Save className="mr-2 h-4 w-4" />
                                                Crear Conteo
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </form>
            </div>
        </AppLayout>
    );
}
