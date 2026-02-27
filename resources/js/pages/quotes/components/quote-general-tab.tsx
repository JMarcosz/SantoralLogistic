/* eslint-disable @typescript-eslint/no-explicit-any */
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
import { Separator } from '@/components/ui/separator';
import { Customer } from '@/types';
import { useMemo } from 'react';
import { Carrier, Division, IssuingCompany, Project } from './types';

interface Props {
    data: any;
    setData: (key: string, value: any) => void;
    errors: Record<string, string>;
    customers: Customer[];
    projects: Project[];
    issuingCompanies: IssuingCompany[];
    carriers: Carrier[];
    divisions: Division[];
}

export function QuoteGeneralTab({
    data,
    setData,
    errors,
    customers,
    projects,
    issuingCompanies,
    carriers,
    divisions,
}: Props) {
    const selectedCustomer = useMemo(
        () => customers.find((c) => c.id === Number(data.customer_id)),
        [customers, data.customer_id],
    );

    return (
        <div className="space-y-6">
            {/* Header Info */}
            <Card>
                <CardHeader>
                    <CardTitle>Datos Generales</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    {/* Division */}
                    <div className="space-y-2">
                        <Label>División *</Label>
                        <Select
                            value={String(data.division_id || '')}
                            onValueChange={(v) =>
                                setData('division_id', v ? Number(v) : '')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar" />
                            </SelectTrigger>
                            <SelectContent>
                                {divisions.map((d) => (
                                    <SelectItem key={d.id} value={String(d.id)}>
                                        {d.name} ({d.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.division_id && (
                            <p className="text-sm text-destructive">
                                {errors.division_id}
                            </p>
                        )}
                    </div>

                    {/* Project */}
                    <div className="space-y-2">
                        <Label>Proyecto</Label>
                        <Select
                            value={String(data.project_id || '')}
                            onValueChange={(v) =>
                                setData('project_id', v ? Number(v) : '')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar proyecto" />
                            </SelectTrigger>
                            <SelectContent>
                                {projects.map((p) => (
                                    <SelectItem key={p.id} value={String(p.id)}>
                                        {p.name} ({p.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.project_id && (
                            <p className="text-sm text-destructive">
                                {errors.project_id}
                            </p>
                        )}
                    </div>

                    {/* Valid Until */}
                    <div className="space-y-2">
                        <Label>Válido Hasta</Label>
                        <Input
                            type="date"
                            value={data.valid_until || ''}
                            onChange={(e) =>
                                setData('valid_until', e.target.value)
                            }
                        />
                        {errors.valid_until && (
                            <p className="text-sm text-destructive">
                                {errors.valid_until}
                            </p>
                        )}
                    </div>

                    {/* Issuing Company */}
                    <div className="space-y-2">
                        <Label>Empresa Emisora *</Label>
                        <Select
                            value={String(data.issuing_company_id || '')}
                            onValueChange={(v) =>
                                setData('issuing_company_id', Number(v))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar empresa" />
                            </SelectTrigger>
                            <SelectContent>
                                {issuingCompanies.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.issuing_company_id && (
                            <p className="text-sm text-destructive">
                                {errors.issuing_company_id}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>

            <Separator />

            {/* Stakeholders */}
            <Card>
                <CardHeader>
                    <CardTitle>Participantes</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-6 md:grid-cols-2">
                    {/* Customer (Bill To) */}
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label>Cliente (Bill To) *</Label>
                            <Select
                                value={String(data.customer_id || '')}
                                onValueChange={(v) =>
                                    setData('customer_id', Number(v))
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar cliente" />
                                </SelectTrigger>
                                <SelectContent>
                                    {customers.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={String(c.id)}
                                        >
                                            {c.name} {c.code && `(${c.code})`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.customer_id && (
                                <p className="text-sm text-destructive">
                                    {errors.customer_id}
                                </p>
                            )}
                        </div>
                        {/* Address Preview */}
                        <div className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                            <p className="mb-1 font-semibold">
                                Dirección de Facturación:
                            </p>
                            {selectedCustomer ? (
                                <div className="space-y-0.5">
                                    <p>
                                        {selectedCustomer.billing_address ||
                                            'Sin dirección'}
                                    </p>
                                    <p>
                                        {[
                                            selectedCustomer.city,
                                            selectedCustomer.country,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')}
                                    </p>
                                </div>
                            ) : (
                                <p className="italic">
                                    Seleccione un cliente...
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Shipper */}
                    <div className="space-y-2">
                        <Label>Shipper</Label>
                        <Select
                            value={String(data.shipper_id || '')}
                            onValueChange={(v) =>
                                setData('shipper_id', v ? Number(v) : '')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Igual que cliente" />
                            </SelectTrigger>
                            <SelectContent>
                                {customers.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
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
                        <Label>Consignee</Label>
                        <Select
                            value={String(data.consignee_id || '')}
                            onValueChange={(v) =>
                                setData('consignee_id', v ? Number(v) : '')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar consignee" />
                            </SelectTrigger>
                            <SelectContent>
                                {customers.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
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

                    {/* Carrier */}
                    <div className="space-y-2">
                        <Label>Carrier</Label>
                        <Select
                            value={String(data.carrier_id || '')}
                            onValueChange={(v) =>
                                setData('carrier_id', v ? Number(v) : '')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar carrier" />
                            </SelectTrigger>
                            <SelectContent>
                                {carriers.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name} ({c.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.carrier_id && (
                            <p className="text-sm text-destructive">
                                {errors.carrier_id}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
