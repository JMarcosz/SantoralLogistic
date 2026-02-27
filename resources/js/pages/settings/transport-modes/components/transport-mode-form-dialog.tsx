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
import { type TransportMode } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface TransportModeFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    transportMode?: TransportMode | null;
}

export default function TransportModeFormDialog({
    open,
    onOpenChange,
    transportMode,
}: TransportModeFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        code: transportMode?.code || '',
        name: transportMode?.name || '',
        description: transportMode?.description || '',
        supports_awb: transportMode?.supports_awb ?? false,
        supports_bl: transportMode?.supports_bl ?? false,
        supports_pod: transportMode?.supports_pod ?? true,
        is_active: transportMode?.is_active ?? true,
    });

    useEffect(() => {
        if (open && transportMode) {
            setData({
                code: transportMode.code,
                name: transportMode.name,
                description: transportMode.description || '',
                supports_awb: transportMode.supports_awb,
                supports_bl: transportMode.supports_bl,
                supports_pod: transportMode.supports_pod,
                is_active: transportMode.is_active,
            });
        } else if (!open) {
            reset();
        }
    }, [open, transportMode, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (transportMode) {
            router.put(`/settings/transport-modes/${transportMode.id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            router.post('/settings/transport-modes', data, {
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
            <DialogContent className="sm:max-w-[500px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {transportMode
                                ? 'Editar Modo de Transporte'
                                : 'Nuevo Modo de Transporte'}
                        </DialogTitle>
                        <DialogDescription>
                            Define cómo se transporta la carga y qué documentos
                            aplican.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Code + Name */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="code" className="text-sm">
                                    Código{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="code"
                                    placeholder="AIR"
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
                                    <p className="text-xs text-destructive">
                                        {errors.code}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="name" className="text-sm">
                                    Nombre{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    placeholder="Aéreo"
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
                                    <p className="text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Description */}
                        <div className="grid gap-1.5">
                            <Label htmlFor="description" className="text-sm">
                                Descripción
                            </Label>
                            <Textarea
                                id="description"
                                placeholder="Descripción del modo de transporte..."
                                maxLength={1000}
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                rows={2}
                            />
                        </div>

                        {/* Document Support */}
                        <div className="space-y-3 rounded-lg border p-4">
                            <p className="text-sm font-medium">
                                Documentos Soportados
                            </p>

                            <div className="flex flex-wrap gap-6">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="supports_awb"
                                        checked={data.supports_awb}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'supports_awb',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="supports_awb"
                                        className="cursor-pointer text-sm font-normal"
                                    >
                                        AWB (Air Waybill)
                                    </Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="supports_bl"
                                        checked={data.supports_bl}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'supports_bl',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="supports_bl"
                                        className="cursor-pointer text-sm font-normal"
                                    >
                                        B/L (Bill of Lading)
                                    </Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="supports_pod"
                                        checked={data.supports_pod}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'supports_pod',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor="supports_pod"
                                        className="cursor-pointer text-sm font-normal"
                                    >
                                        POD (Proof of Delivery)
                                    </Label>
                                </div>
                            </div>
                        </div>

                        {/* Active */}
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
                                Modo de transporte activo
                            </Label>
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
                            {transportMode ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
