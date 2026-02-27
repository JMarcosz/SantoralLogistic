import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Ship } from 'lucide-react';
import {
    ContainerDetailsTable,
    type ContainerDetail,
} from './container-details-table';

export interface OceanShipmentData {
    mbl_number: string;
    hbl_number: string;
    carrier_name: string;
    vessel_name: string;
    voyage_number: string;
    container_details: ContainerDetail[];
}

interface OceanShipmentFormProps {
    data: OceanShipmentData;
    onChange: (
        field: keyof OceanShipmentData,
        value: string | ContainerDetail[],
    ) => void;
    errors?: Record<string, string>;
}

export function OceanShipmentForm({
    data,
    onChange,
    errors = {},
}: OceanShipmentFormProps) {
    return (
        <Card className="border-blue-500/30 bg-blue-500/5">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Ship className="h-5 w-5 text-blue-500" />
                    Detalles Marítimos
                </CardTitle>
                <CardDescription>
                    Información del embarque marítimo
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Bill of Lading Numbers */}
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="mbl_number">MBL (Master B/L)</Label>
                        <Input
                            id="mbl_number"
                            value={data.mbl_number}
                            onChange={(e) =>
                                onChange('mbl_number', e.target.value)
                            }
                            placeholder="Número de Master Bill of Lading"
                            className={
                                errors['ocean_shipment.mbl_number']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                        {errors['ocean_shipment.mbl_number'] && (
                            <p className="text-sm text-destructive">
                                {errors['ocean_shipment.mbl_number']}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="hbl_number">HBL (House B/L)</Label>
                        <Input
                            id="hbl_number"
                            value={data.hbl_number}
                            onChange={(e) =>
                                onChange('hbl_number', e.target.value)
                            }
                            placeholder="Número de House Bill of Lading"
                            className={
                                errors['ocean_shipment.hbl_number']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                        {errors['ocean_shipment.hbl_number'] && (
                            <p className="text-sm text-destructive">
                                {errors['ocean_shipment.hbl_number']}
                            </p>
                        )}
                    </div>
                </div>

                {/* Carrier and Vessel Info */}
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="space-y-2">
                        <Label htmlFor="carrier_name">Naviera</Label>
                        <Input
                            id="carrier_name"
                            value={data.carrier_name}
                            onChange={(e) =>
                                onChange('carrier_name', e.target.value)
                            }
                            placeholder="Ej: Maersk, MSC"
                            className={
                                errors['ocean_shipment.carrier_name']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="vessel_name">Buque</Label>
                        <Input
                            id="vessel_name"
                            value={data.vessel_name}
                            onChange={(e) =>
                                onChange('vessel_name', e.target.value)
                            }
                            placeholder="Nombre del buque"
                            className={
                                errors['ocean_shipment.vessel_name']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="voyage_number">Viaje</Label>
                        <Input
                            id="voyage_number"
                            value={data.voyage_number}
                            onChange={(e) =>
                                onChange('voyage_number', e.target.value)
                            }
                            placeholder="Número de viaje"
                            className={
                                errors['ocean_shipment.voyage_number']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                    </div>
                </div>

                {/* Container Details */}
                <ContainerDetailsTable
                    containers={data.container_details}
                    onChange={(containers) =>
                        onChange('container_details', containers)
                    }
                />
            </CardContent>
        </Card>
    );
}
