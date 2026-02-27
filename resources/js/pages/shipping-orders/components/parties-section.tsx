import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Users } from 'lucide-react';

interface Customer {
    id: number;
    name: string;
    code: string | null;
}

interface PartiesSectionProps {
    customers: Customer[];
    shipperId: number | null;
    consigneeId: number | null;
    onShipperChange: (id: number | null) => void;
    onConsigneeChange: (id: number | null) => void;
    errors?: {
        shipper_id?: string;
        consignee_id?: string;
    };
}

export function PartiesSection({
    customers,
    shipperId,
    consigneeId,
    onShipperChange,
    onConsigneeChange,
    errors = {},
}: PartiesSectionProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Users className="h-5 w-5" />
                    Partes del Embarque
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid gap-4 md:grid-cols-2">
                    {/* Shipper */}
                    <div className="space-y-2">
                        <Label htmlFor="shipper_id">Shipper (Exportador)</Label>
                        <Select
                            value={shipperId?.toString() ?? ''}
                            onValueChange={(val) =>
                                onShipperChange(val ? parseInt(val, 10) : null)
                            }
                        >
                            <SelectTrigger
                                className={
                                    errors.shipper_id
                                        ? 'border-destructive'
                                        : ''
                                }
                            >
                                <SelectValue placeholder="Seleccionar shipper (opcional)" />
                            </SelectTrigger>
                            <SelectContent>
                                {customers.map((c) => (
                                    <SelectItem
                                        key={c.id}
                                        value={c.id.toString()}
                                    >
                                        {c.code
                                            ? `${c.code} - ${c.name}`
                                            : c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.shipper_id && (
                            <p className="text-sm text-destructive">
                                {errors.shipper_id}
                            </p>
                        )}
                    </div>

                    {/* Consignee */}
                    <div className="space-y-2">
                        <Label htmlFor="consignee_id">
                            Consignee (Importador)
                        </Label>
                        <Select
                            value={consigneeId?.toString() ?? ''}
                            onValueChange={(val) =>
                                onConsigneeChange(
                                    val ? parseInt(val, 10) : null,
                                )
                            }
                        >
                            <SelectTrigger
                                className={
                                    errors.consignee_id
                                        ? 'border-destructive'
                                        : ''
                                }
                            >
                                <SelectValue placeholder="Seleccionar consignee (opcional)" />
                            </SelectTrigger>
                            <SelectContent>
                                {customers.map((c) => (
                                    <SelectItem
                                        key={c.id}
                                        value={c.id.toString()}
                                    >
                                        {c.code
                                            ? `${c.code} - ${c.name}`
                                            : c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.consignee_id && (
                            <p className="text-sm text-destructive">
                                {errors.consignee_id}
                            </p>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
