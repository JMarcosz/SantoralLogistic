import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type ServiceType } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface ServiceTypeFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    serviceType?: ServiceType | null;
}

export default function ServiceTypeFormDialog({
    open,
    onOpenChange,
    serviceType,
}: ServiceTypeFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        code: serviceType?.code || '',
        name: serviceType?.name || '',
        description: serviceType?.description || '',
        scope: serviceType?.scope || '',
        default_incoterm: serviceType?.default_incoterm || '',
        is_active: serviceType?.is_active ?? true,
        is_default: serviceType?.is_default ?? false,
    });

    // Reset form when dialog opens/closes or serviceType changes
    useEffect(() => {
        if (open && serviceType) {
            setData({
                code: serviceType.code,
                name: serviceType.name,
                description: serviceType.description || '',
                scope: serviceType.scope || '',
                default_incoterm: serviceType.default_incoterm || '',
                is_active: serviceType.is_active,
                is_default: serviceType.is_default,
            });
        } else if (!open) {
            reset();
        }
    }, [open, serviceType, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (serviceType) {
            // Update existing service type
            router.put(`/settings/service-types/${serviceType.id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            // Create new service type
            router.post('/settings/service-types', data, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[550px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {serviceType
                                ? 'Editar Tipo de Servicio'
                                : 'Nuevo Tipo de Servicio'}
                        </DialogTitle>
                        <DialogDescription>
                            {serviceType
                                ? 'Actualiza la información del tipo de servicio.'
                                : 'Agrega un nuevo tipo de servicio logístico.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Row 1: Code and Name */}
                        <div className="grid grid-cols-2 gap-4">
                            {/* Code */}
                            <div className="grid gap-2">
                                <Label htmlFor="code">
                                    Código{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="code"
                                    placeholder="D2D"
                                    maxLength={20}
                                    value={data.code}
                                    onChange={(e) =>
                                        setData(
                                            'code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    className={
                                        errors.code ? 'border-destructive' : ''
                                    }
                                    required
                                />
                                {errors.code && (
                                    <p className="text-sm text-destructive">
                                        {errors.code}
                                    </p>
                                )}
                            </div>

                            {/* Name */}
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    Nombre{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    placeholder="Door to Door"
                                    maxLength={100}
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    className={
                                        errors.name ? 'border-destructive' : ''
                                    }
                                    required
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Row 2: Description */}
                        <div className="grid gap-2">
                            <Label htmlFor="description">Descripción</Label>
                            <Textarea
                                id="description"
                                placeholder="Describe el tipo de servicio..."
                                maxLength={1000}
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                className={
                                    errors.description
                                        ? 'border-destructive'
                                        : ''
                                }
                                rows={3}
                            />
                            {errors.description && (
                                <p className="text-sm text-destructive">
                                    {errors.description}
                                </p>
                            )}
                        </div>

                        {/* Row 3: Scope and Incoterm */}
                        <div className="grid grid-cols-2 gap-4">
                            {/* Scope */}
                            <div className="grid gap-2">
                                <Label htmlFor="scope">Alcance</Label>
                                <Input
                                    id="scope"
                                    placeholder="international"
                                    maxLength={50}
                                    value={data.scope}
                                    onChange={(e) =>
                                        setData('scope', e.target.value)
                                    }
                                    className={
                                        errors.scope ? 'border-destructive' : ''
                                    }
                                />
                                {errors.scope && (
                                    <p className="text-sm text-destructive">
                                        {errors.scope}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Ej: international, domestic
                                </p>
                            </div>

                            {/* Default Incoterm */}
                            <div className="grid gap-2">
                                <Label htmlFor="default_incoterm">
                                    Incoterm Predeterminado
                                </Label>
                                <Input
                                    id="default_incoterm"
                                    placeholder="DDP"
                                    maxLength={10}
                                    value={data.default_incoterm}
                                    onChange={(e) =>
                                        setData(
                                            'default_incoterm',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    className={
                                        errors.default_incoterm
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                                {errors.default_incoterm && (
                                    <p className="text-sm text-destructive">
                                        {errors.default_incoterm}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Ej: DDP, DAP, FOB, CIF
                                </p>
                            </div>
                        </div>

                        {/* Row 4: Checkboxes */}
                        <div className="flex flex-col gap-3">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="is_active"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    Tipo de servicio activo
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_default"
                                    checked={data.is_default}
                                    onCheckedChange={(checked) =>
                                        setData('is_default', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="is_default"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    Establecer como tipo predeterminado
                                </Label>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            {serviceType ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
