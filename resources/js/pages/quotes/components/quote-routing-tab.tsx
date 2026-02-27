import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { AddressAutocomplete } from '@/components/address-autocomplete';
import { Port, ServiceType, TransportMode } from '@/types';
import { QuoteFormValues } from './quote-form';

interface Props {
    data: QuoteFormValues;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setData: (field: keyof QuoteFormValues, value: any) => void;
    errors: Partial<Record<string, string>>;
    ports: Port[];
    transportModes: TransportMode[];
    serviceTypes: ServiceType[];
}

export function QuoteRoutingTab({
    data,
    setData,
    errors,
    ports,
    transportModes,
    serviceTypes,
}: Props) {
    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Ruta y Servicio</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* Origin Port */}
                    <div className="space-y-2">
                        <Label>Puerto Origen *</Label>
                        <Select
                            value={String(data.origin_port_id || '')}
                            onValueChange={(v) =>
                                setData('origin_port_id', Number(v))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar origen" />
                            </SelectTrigger>
                            <SelectContent>
                                {ports.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>
                                        {p.code} - {p.name} ({p.country})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.origin_port_id && (
                            <p className="text-sm text-destructive">
                                {errors.origin_port_id}
                            </p>
                        )}
                    </div>

                    {/* Destination Port */}
                    <div className="space-y-2">
                        <Label>Puerto Destino *</Label>
                        <Select
                            value={String(data.destination_port_id || '')}
                            onValueChange={(v) =>
                                setData('destination_port_id', Number(v))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar destino" />
                            </SelectTrigger>
                            <SelectContent>
                                {ports.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>
                                        {p.code} - {p.name} ({p.country})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.destination_port_id && (
                            <p className="text-sm text-destructive">
                                {errors.destination_port_id}
                            </p>
                        )}
                    </div>

                    {/* Transit Days */}
                    <div className="space-y-2">
                        <Label>Días de Tránsito</Label>
                        <Input
                            type="number"
                            min="0"
                            value={data.transit_days || ''}
                            onChange={(e) =>
                                setData('transit_days', e.target.value)
                            }
                            placeholder="Ej. 15"
                        />
                        {errors.transit_days && (
                            <p className="text-sm text-destructive">
                                {errors.transit_days}
                            </p>
                        )}
                    </div>

                    {/* Transport Mode */}
                    <div className="space-y-2">
                        <Label>Modo Transporte *</Label>
                        <Select
                            value={String(data.transport_mode_id || '')}
                            onValueChange={(v) =>
                                setData('transport_mode_id', Number(v))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar modo" />
                            </SelectTrigger>
                            <SelectContent>
                                {transportModes.map((m) => (
                                    <SelectItem key={m.id} value={String(m.id)}>
                                        {m.name} ({m.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.transport_mode_id && (
                            <p className="text-sm text-destructive">
                                {errors.transport_mode_id}
                            </p>
                        )}
                    </div>

                    {/* Service Type */}
                    <div className="space-y-2">
                        <Label>Tipo Servicio *</Label>
                        <Select
                            value={String(data.service_type_id || '')}
                            onValueChange={(v) =>
                                setData('service_type_id', Number(v))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar servicio" />
                            </SelectTrigger>
                            <SelectContent>
                                {serviceTypes.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.name} ({s.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.service_type_id && (
                            <p className="text-sm text-destructive">
                                {errors.service_type_id}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        Direcciones Logísticas (Pickup / Delivery)
                    </CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    {/* Pickup Address */}
                    <div className="space-y-2">
                        <Label>Dirección de Recolección (Pickup)</Label>
                        <AddressAutocomplete
                            value={data.pickup_address}
                            onChange={(val) => setData('pickup_address', val)}
                            placeholder="Buscar dirección o escribir..."
                        />
                        {errors.pickup_address && (
                            <p className="text-sm text-destructive">
                                {errors.pickup_address}
                            </p>
                        )}
                    </div>

                    {/* Delivery Address */}
                    <div className="space-y-2">
                        <Label>Dirección de Entrega (Delivery)</Label>
                        <AddressAutocomplete
                            value={data.delivery_address}
                            onChange={(val) => setData('delivery_address', val)}
                            placeholder="Buscar dirección o escribir..."
                        />
                        {errors.delivery_address && (
                            <p className="text-sm text-destructive">
                                {errors.delivery_address}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
