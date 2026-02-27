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
import { type Rate } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface Port {
    id: number;
    code: string;
    name: string;
    type: string;
}

interface TransportMode {
    id: number;
    code: string;
    name: string;
}

interface ServiceType {
    id: number;
    code: string;
    name: string;
}

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface RateFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    rate?: Rate | null;
    ports: Port[];
    transportModes: TransportMode[];
    serviceTypes: ServiceType[];
    currencies: Currency[];
}

export default function RateFormDialog({
    open,
    onOpenChange,
    rate,
    ports,
    transportModes,
    serviceTypes,
    currencies,
}: RateFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        origin_port_id: rate?.origin_port_id?.toString() || '',
        destination_port_id: rate?.destination_port_id?.toString() || '',
        transport_mode_id: rate?.transport_mode_id?.toString() || '',
        service_type_id: rate?.service_type_id?.toString() || '',
        currency_id: rate?.currency_id?.toString() || '',
        charge_basis: rate?.charge_basis || 'per_shipment',
        base_amount: rate?.base_amount?.toString() || '',
        min_amount: rate?.min_amount?.toString() || '',
        valid_from: rate?.valid_from || new Date().toISOString().split('T')[0],
        valid_to: rate?.valid_to || '',
        is_active: rate?.is_active ?? true,
    });

    useEffect(() => {
        if (open && rate) {
            setData({
                origin_port_id: rate.origin_port_id.toString(),
                destination_port_id: rate.destination_port_id.toString(),
                transport_mode_id: rate.transport_mode_id.toString(),
                service_type_id: rate.service_type_id.toString(),
                currency_id: rate.currency_id.toString(),
                charge_basis: rate.charge_basis,
                base_amount: rate.base_amount.toString(),
                min_amount: rate.min_amount?.toString() || '',
                valid_from: rate.valid_from,
                valid_to: rate.valid_to || '',
                is_active: rate.is_active,
            });
        } else if (!open) {
            reset();
        }
    }, [open, rate, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const payload = {
            ...data,
            origin_port_id: parseInt(data.origin_port_id),
            destination_port_id: parseInt(data.destination_port_id),
            transport_mode_id: parseInt(data.transport_mode_id),
            service_type_id: parseInt(data.service_type_id),
            currency_id: parseInt(data.currency_id),
            base_amount: parseFloat(data.base_amount),
            min_amount: data.min_amount ? parseFloat(data.min_amount) : null,
            valid_to: data.valid_to || null,
        };

        if (rate) {
            router.put(`/settings/rates/${rate.id}`, payload, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            router.post('/settings/rates', payload, {
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
            <DialogContent className="sm:max-w-[650px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {rate ? 'Editar Tarifa' : 'Nueva Tarifa'}
                        </DialogTitle>
                        <DialogDescription>
                            Define precios por carril y servicio.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Lane: Origin → Destination */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="origin_port_id"
                                    className="text-sm"
                                >
                                    Puerto Origen{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.origin_port_id}
                                    onValueChange={(value) =>
                                        setData('origin_port_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="origin_port_id"
                                        className={
                                            errors.origin_port_id
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar origen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ports.map((port) => (
                                            <SelectItem
                                                key={port.id}
                                                value={port.id.toString()}
                                            >
                                                {port.code} - {port.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.origin_port_id && (
                                    <p className="text-xs text-destructive">
                                        {errors.origin_port_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="destination_port_id"
                                    className="text-sm"
                                >
                                    Puerto Destino{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.destination_port_id}
                                    onValueChange={(value) =>
                                        setData('destination_port_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="destination_port_id"
                                        className={
                                            errors.destination_port_id
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar destino" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ports.map((port) => (
                                            <SelectItem
                                                key={port.id}
                                                value={port.id.toString()}
                                            >
                                                {port.code} - {port.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.destination_port_id && (
                                    <p className="text-xs text-destructive">
                                        {errors.destination_port_id}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Mode + Service */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="transport_mode_id"
                                    className="text-sm"
                                >
                                    Modo de Transporte{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.transport_mode_id}
                                    onValueChange={(value) =>
                                        setData('transport_mode_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="transport_mode_id"
                                        className={
                                            errors.transport_mode_id
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {transportModes.map((mode) => (
                                            <SelectItem
                                                key={mode.id}
                                                value={mode.id.toString()}
                                            >
                                                {mode.code} - {mode.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="service_type_id"
                                    className="text-sm"
                                >
                                    Tipo de Servicio{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.service_type_id}
                                    onValueChange={(value) =>
                                        setData('service_type_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="service_type_id"
                                        className={
                                            errors.service_type_id
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {serviceTypes.map((st) => (
                                            <SelectItem
                                                key={st.id}
                                                value={st.id.toString()}
                                            >
                                                {st.code} - {st.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Currency + Charge Basis */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="currency_id"
                                    className="text-sm"
                                >
                                    Moneda{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.currency_id}
                                    onValueChange={(value) =>
                                        setData('currency_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="currency_id"
                                        className={
                                            errors.currency_id
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {currencies.map((curr) => (
                                            <SelectItem
                                                key={curr.id}
                                                value={curr.id.toString()}
                                            >
                                                {curr.code} - {curr.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="charge_basis"
                                    className="text-sm"
                                >
                                    Base de Cargo{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.charge_basis}
                                    onValueChange={(value) =>
                                        setData(
                                            'charge_basis',
                                            value as
                                                | 'per_shipment'
                                                | 'per_kg'
                                                | 'per_cbm'
                                                | 'per_container',
                                        )
                                    }
                                >
                                    <SelectTrigger id="charge_basis">
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="per_shipment">
                                            Por Embarque
                                        </SelectItem>
                                        <SelectItem value="per_kg">
                                            Por Kg
                                        </SelectItem>
                                        <SelectItem value="per_cbm">
                                            Por CBM
                                        </SelectItem>
                                        <SelectItem value="per_container">
                                            Por Contenedor
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Amounts */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="base_amount"
                                    className="text-sm"
                                >
                                    Monto Base{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="base_amount"
                                    type="number"
                                    step="0.0001"
                                    min="0.0001"
                                    placeholder="0.00"
                                    value={data.base_amount}
                                    onChange={(e) =>
                                        setData('base_amount', e.target.value)
                                    }
                                    className={
                                        errors.base_amount
                                            ? 'border-destructive'
                                            : ''
                                    }
                                    required
                                />
                                {errors.base_amount && (
                                    <p className="text-xs text-destructive">
                                        {errors.base_amount}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="min_amount" className="text-sm">
                                    Monto Mínimo
                                </Label>
                                <Input
                                    id="min_amount"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.min_amount}
                                    onChange={(e) =>
                                        setData('min_amount', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        {/* Validity */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="valid_from" className="text-sm">
                                    Válido Desde{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="valid_from"
                                    type="date"
                                    value={data.valid_from}
                                    onChange={(e) =>
                                        setData('valid_from', e.target.value)
                                    }
                                    className={
                                        errors.valid_from
                                            ? 'border-destructive'
                                            : ''
                                    }
                                    required
                                />
                                {errors.valid_from && (
                                    <p className="text-xs text-destructive">
                                        {errors.valid_from}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="valid_to" className="text-sm">
                                    Válido Hasta
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
                                    <p className="text-xs text-destructive">
                                        {errors.valid_to}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Active */}
                        <div className="flex items-center space-x-2 pt-2">
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
                                Tarifa activa
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
                            {rate ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
