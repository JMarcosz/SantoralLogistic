import { Alert, AlertDescription } from '@/components/ui/alert';
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
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, FileText, Save } from 'lucide-react';

interface FiscalSequence {
    id: number;
    ncf_type: string;
    series?: string | null;
    ncf_from: string;
    ncf_to: string;
    current_ncf?: string | null;
    valid_from: string;
    valid_to: string;
    is_active: boolean;
}

interface Props {
    ncfTypes: Record<string, string>;
    sequence?: FiscalSequence | null;
}

export default function FiscalSequencesForm({ ncfTypes, sequence }: Props) {
    const isEditing = !!sequence;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Administración',
            href: '#',
        },
        {
            title: 'Rangos NCF',
            href: '/admin/fiscal-sequences',
        },
        {
            title: isEditing ? 'Editar Rango' : 'Nuevo Rango',
            href: '#',
        },
    ];

    const { data, setData, post, put, errors, processing } = useForm({
        ncf_type: sequence?.ncf_type || '',
        series: sequence?.series || '',
        ncf_from: sequence?.ncf_from || '',
        ncf_to: sequence?.ncf_to || '',
        valid_from: sequence?.valid_from || '',
        valid_to: sequence?.valid_to || '',
        is_active: sequence?.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEditing) {
            put(`/admin/fiscal-sequences/${sequence.id}`, {
                preserveScroll: true,
            });
        } else {
            post('/admin/fiscal-sequences', {
                preserveScroll: true,
            });
        }
    };

    const handleCancel = () => {
        router.get('/admin/fiscal-sequences');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isEditing ? 'Editar Rango NCF' : 'Nuevo Rango NCF'} />

            <div className="container mx-auto max-w-4xl space-y-8 px-4 py-8">
                {/* Header */}
                <div className="shadow-premium-lg relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-8">
                    <div className="absolute top-0 right-0 h-40 w-40 translate-x-10 -translate-y-10 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-start gap-6">
                        <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/50">
                            <FileText className="h-8 w-8 text-primary-foreground" />
                        </div>
                        <div className="space-y-1">
                            <h1 className="text-3xl font-bold tracking-tight">
                                {isEditing
                                    ? 'Editar Rango NCF'
                                    : 'Nuevo Rango NCF'}
                            </h1>
                            <p className="text-muted-foreground">
                                {isEditing
                                    ? 'Modifica los detalles del rango de comprobantes fiscales'
                                    : 'Crea un nuevo rango de comprobantes fiscales'}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Rango</CardTitle>
                            <CardDescription>
                                Complete los campos para{' '}
                                {isEditing ? 'actualizar el' : 'crear un nuevo'}{' '}
                                rango de NCF
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Overlap Error Alert */}
                            {errors.ncf_from && (
                                <Alert variant="destructive">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        {errors.ncf_from}
                                    </AlertDescription>
                                </Alert>
                            )}

                            {/* NCF Type */}
                            <div className="space-y-2">
                                <Label htmlFor="ncf_type">
                                    Tipo de NCF{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.ncf_type}
                                    onValueChange={(value) =>
                                        setData('ncf_type', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="ncf_type"
                                        className={
                                            errors.ncf_type
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccione un tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(ncfTypes).map(
                                            ([code, label]) => (
                                                <SelectItem
                                                    key={code}
                                                    value={code}
                                                >
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono font-semibold">
                                                            {code}
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {label}
                                                        </span>
                                                    </div>
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                {errors.ncf_type && (
                                    <p className="text-sm text-destructive">
                                        {errors.ncf_type}
                                    </p>
                                )}
                            </div>

                            {/* Series */}
                            <div className="space-y-2">
                                <Label htmlFor="series">Serie (Opcional)</Label>
                                <Input
                                    id="series"
                                    value={data.series}
                                    onChange={(e) =>
                                        setData('series', e.target.value)
                                    }
                                    placeholder="Ej: 001, STO"
                                    className={`font-mono ${errors.series ? 'border-destructive' : ''}`}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Identificador de sucursal o serie interna
                                </p>
                                {errors.series && (
                                    <p className="text-sm text-destructive">
                                        {errors.series}
                                    </p>
                                )}
                            </div>

                            {/* NCF Range */}
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="ncf_from">
                                        NCF Desde{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="ncf_from"
                                        value={data.ncf_from}
                                        onChange={(e) =>
                                            setData('ncf_from', e.target.value)
                                        }
                                        placeholder="E310000000001"
                                        className={`font-mono ${errors.ncf_from ? 'border-destructive' : ''}`}
                                    />
                                    {errors.ncf_from && (
                                        <p className="text-sm text-destructive">
                                            {errors.ncf_from}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="ncf_to">
                                        NCF Hasta{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="ncf_to"
                                        value={data.ncf_to}
                                        onChange={(e) =>
                                            setData('ncf_to', e.target.value)
                                        }
                                        placeholder="E310000001000"
                                        className={`font-mono ${errors.ncf_to ? 'border-destructive' : ''}`}
                                    />
                                    {errors.ncf_to && (
                                        <p className="text-sm text-destructive">
                                            {errors.ncf_to}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Validity Dates */}
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="valid_from">
                                        Vigencia Desde{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="valid_from"
                                        type="date"
                                        value={data.valid_from}
                                        onChange={(e) =>
                                            setData(
                                                'valid_from',
                                                e.target.value,
                                            )
                                        }
                                        className={
                                            errors.valid_from
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                    {errors.valid_from && (
                                        <p className="text-sm text-destructive">
                                            {errors.valid_from}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="valid_to">
                                        Vigencia Hasta{' '}
                                        <span className="text-destructive">
                                            *
                                        </span>
                                    </Label>
                                    <Input
                                        id="valid_to"
                                        type="date"
                                        value={data.valid_to}
                                        onChange={(e) =>
                                            setData('valid_to', e.target.value)
                                        }
                                        className={
                                            errors.valid_to
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                    {errors.valid_to && (
                                        <p className="text-sm text-destructive">
                                            {errors.valid_to}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Active Switch */}
                            <div className="flex items-center justify-between rounded-lg border p-4">
                                <div className="space-y-0.5">
                                    <Label
                                        htmlFor="is_active"
                                        className="text-base"
                                    >
                                        Rango Activo
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Los rangos inactivos no emiten NCFs
                                    </p>
                                </div>
                                <Switch
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked)
                                    }
                                />
                            </div>

                            {/* Current NCF Info */}
                            {isEditing && sequence?.current_ncf && (
                                <Alert>
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <strong>NCF Actual:</strong>{' '}
                                        <span className="font-mono">
                                            {sequence.current_ncf}
                                        </span>
                                        <br />
                                        <span className="text-sm">
                                            No se puede modificar el rango por
                                            debajo del NCF actual
                                        </span>
                                    </AlertDescription>
                                </Alert>
                            )}
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4 pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCancel}
                            disabled={processing}
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {isEditing ? 'Actualizar' : 'Crear'} Rango
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
