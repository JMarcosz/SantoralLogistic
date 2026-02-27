import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plane } from 'lucide-react';

export interface AirShipmentData {
    mawb_number: string;
    hawb_number: string;
    airline_name: string;
    flight_number: string;
}

interface AirShipmentFormProps {
    data: AirShipmentData;
    onChange: (field: keyof AirShipmentData, value: string) => void;
    errors?: Record<string, string>;
}

export function AirShipmentForm({
    data,
    onChange,
    errors = {},
}: AirShipmentFormProps) {
    return (
        <Card className="border-amber-500/30 bg-amber-500/5">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Plane className="h-5 w-5 text-amber-500" />
                    Detalles Aéreos
                </CardTitle>
                <CardDescription>
                    Información del embarque aéreo
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Air Waybill Numbers */}
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="mawb_number">MAWB (Master AWB)</Label>
                        <Input
                            id="mawb_number"
                            value={data.mawb_number}
                            onChange={(e) =>
                                onChange('mawb_number', e.target.value)
                            }
                            placeholder="Número de Master Air Waybill"
                            className={
                                errors['air_shipment.mawb_number']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                        {errors['air_shipment.mawb_number'] && (
                            <p className="text-sm text-destructive">
                                {errors['air_shipment.mawb_number']}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="hawb_number">HAWB (House AWB)</Label>
                        <Input
                            id="hawb_number"
                            value={data.hawb_number}
                            onChange={(e) =>
                                onChange('hawb_number', e.target.value)
                            }
                            placeholder="Número de House Air Waybill"
                            className={
                                errors['air_shipment.hawb_number']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                        {errors['air_shipment.hawb_number'] && (
                            <p className="text-sm text-destructive">
                                {errors['air_shipment.hawb_number']}
                            </p>
                        )}
                    </div>
                </div>

                {/* Airline and Flight Info */}
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="airline_name">Aerolínea</Label>
                        <Input
                            id="airline_name"
                            value={data.airline_name}
                            onChange={(e) =>
                                onChange('airline_name', e.target.value)
                            }
                            placeholder="Ej: American Airlines, DHL"
                            className={
                                errors['air_shipment.airline_name']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="flight_number">Vuelo</Label>
                        <Input
                            id="flight_number"
                            value={data.flight_number}
                            onChange={(e) =>
                                onChange('flight_number', e.target.value)
                            }
                            placeholder="Número de vuelo"
                            className={
                                errors['air_shipment.flight_number']
                                    ? 'border-destructive'
                                    : ''
                            }
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
