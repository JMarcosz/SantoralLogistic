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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { type Port } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Anchor, Loader2, Plane, Truck } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface PortFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    port?: Port | null;
}

export default function PortFormDialog({
    open,
    onOpenChange,
    port,
}: PortFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        code: port?.code || '',
        name: port?.name || '',
        country: port?.country || '',
        city: port?.city || '',
        unlocode: port?.unlocode || '',
        iata_code: port?.iata_code || '',
        type: port?.type || 'ocean',
        timezone: port?.timezone || '',
        is_active: port?.is_active ?? true,
    });

    // Reset form when dialog opens/closes or port changes
    useEffect(() => {
        if (open && port) {
            setData({
                code: port.code,
                name: port.name,
                country: port.country,
                city: port.city || '',
                unlocode: port.unlocode || '',
                iata_code: port.iata_code || '',
                type: port.type,
                timezone: port.timezone || '',
                is_active: port.is_active,
            });
        } else if (!open) {
            reset();
        }
    }, [open, port, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (port) {
            // Update existing port
            router.put(`/settings/ports/${port.id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            // Create new port
            router.post('/settings/ports', data, {
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
            <DialogContent className="sm:max-w-[600px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {port ? 'Editar Puerto' : 'Nuevo Puerto'}
                        </DialogTitle>
                        <DialogDescription>
                            {port
                                ? 'Actualiza la información del puerto.'
                                : 'Agrega un nuevo puerto, aeropuerto o terminal al sistema.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Row 1: Code and Type */}
                        <div className="grid grid-cols-2 gap-4">
                            {/* Code */}
                            <div className="grid gap-2">
                                <Label htmlFor="code">
                                    Código{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="code"
                                    placeholder="USMIA"
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
                                <p className="text-xs text-muted-foreground">
                                    Código único (preferiblemente UN/LOCODE)
                                </p>
                            </div>

                            {/* Type */}
                            <div className="grid gap-2">
                                <Label htmlFor="type">
                                    Tipo{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value: Port['type']) =>
                                        setData('type', value)
                                    }
                                >
                                    <SelectTrigger
                                        className={
                                            errors.type
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="air">
                                            <div className="flex items-center gap-2">
                                                <Plane className="h-4 w-4" />
                                                Aéreo (Aeropuerto)
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="ocean">
                                            <div className="flex items-center gap-2">
                                                <Anchor className="h-4 w-4" />
                                                Marítimo (Puerto)
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="ground">
                                            <div className="flex items-center gap-2">
                                                <Truck className="h-4 w-4" />
                                                Terrestre (Terminal)
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && (
                                    <p className="text-sm text-destructive">
                                        {errors.type}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Row 2: Name */}
                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Nombre{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                placeholder="Miami International Airport"
                                maxLength={150}
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

                        {/* Row 3: Country and City */}
                        <div className="grid grid-cols-2 gap-4">
                            {/* Country */}
                            <div className="grid gap-2">
                                <Label htmlFor="country">
                                    País{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="country"
                                    placeholder="United States"
                                    maxLength={100}
                                    value={data.country}
                                    onChange={(e) =>
                                        setData('country', e.target.value)
                                    }
                                    className={
                                        errors.country
                                            ? 'border-destructive'
                                            : ''
                                    }
                                    required
                                />
                                {errors.country && (
                                    <p className="text-sm text-destructive">
                                        {errors.country}
                                    </p>
                                )}
                            </div>

                            {/* City */}
                            <div className="grid gap-2">
                                <Label htmlFor="city">Ciudad</Label>
                                <Input
                                    id="city"
                                    placeholder="Miami"
                                    maxLength={100}
                                    value={data.city}
                                    onChange={(e) =>
                                        setData('city', e.target.value)
                                    }
                                    className={
                                        errors.city ? 'border-destructive' : ''
                                    }
                                />
                                {errors.city && (
                                    <p className="text-sm text-destructive">
                                        {errors.city}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Row 4: UN/LOCODE and IATA Code */}
                        <div className="grid grid-cols-2 gap-4">
                            {/* UN/LOCODE */}
                            <div className="grid gap-2">
                                <Label htmlFor="unlocode">UN/LOCODE</Label>
                                <Input
                                    id="unlocode"
                                    placeholder="USMIA"
                                    maxLength={10}
                                    value={data.unlocode}
                                    onChange={(e) =>
                                        setData(
                                            'unlocode',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    className={
                                        errors.unlocode
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                                {errors.unlocode && (
                                    <p className="text-sm text-destructive">
                                        {errors.unlocode}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Código UN/LOCODE completo
                                </p>
                            </div>

                            {/* IATA Code */}
                            <div className="grid gap-2">
                                <Label htmlFor="iata_code">Código IATA</Label>
                                <Input
                                    id="iata_code"
                                    placeholder="MIA"
                                    maxLength={5}
                                    value={data.iata_code}
                                    onChange={(e) =>
                                        setData(
                                            'iata_code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    className={
                                        errors.iata_code
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                                {errors.iata_code && (
                                    <p className="text-sm text-destructive">
                                        {errors.iata_code}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Solo para aeropuertos
                                </p>
                            </div>
                        </div>

                        {/* Row 5: Timezone */}
                        <div className="grid gap-2">
                            <Label htmlFor="timezone">Zona Horaria</Label>
                            <Input
                                id="timezone"
                                placeholder="America/New_York"
                                maxLength={50}
                                value={data.timezone}
                                onChange={(e) =>
                                    setData('timezone', e.target.value)
                                }
                                className={
                                    errors.timezone ? 'border-destructive' : ''
                                }
                            />
                            {errors.timezone && (
                                <p className="text-sm text-destructive">
                                    {errors.timezone}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                Identificador de zona horaria IANA (ej:
                                America/New_York)
                            </p>
                        </div>

                        {/* Row 6: Is Active */}
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
                                Puerto activo (disponible para selección en
                                otros módulos)
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
                            {port ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
