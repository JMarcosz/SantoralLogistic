import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Package, Save } from 'lucide-react';
import { useMemo, useState } from 'react';

// Import modular components
import {
    AirShipmentForm,
    type AirShipmentData,
} from './components/air-shipment-form';
import type { ContainerDetail } from './components/container-details-table';
import {
    OceanShipmentForm,
    type OceanShipmentData,
} from './components/ocean-shipment-form';
import { PartiesSection } from './components/parties-section';

interface Customer {
    id: number;
    name: string;
    code: string | null;
}

interface Port {
    id: number;
    code: string;
    name: string;
    country: string;
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
    symbol: string;
    name: string;
}

interface Term {
    id: number;
    code: string;
    name: string;
}

interface ShippingOrder {
    id: number;
    order_number: string;
    customer_id: number;
    contact_id: number | null;
    shipper_id: number | null;
    consignee_id: number | null;
    origin_port_id: number;
    destination_port_id: number;
    transport_mode_id: number;
    service_type_id: number;
    currency_id: number;
    total_amount: string;
    total_pieces: number;
    total_weight_kg: string;
    total_volume_cbm: string;
    planned_departure_at: string | null;
    planned_arrival_at: string | null;
    pickup_date: string | null;
    delivery_date: string | null;
    notes: string | null;
    footer_terms_id: number | null;
    ocean_shipment: {
        mbl_number: string | null;
        hbl_number: string | null;
        carrier_name: string | null;
        vessel_name: string | null;
        voyage_number: string | null;
        container_details: ContainerDetail[] | null;
    } | null;
    air_shipment: {
        mawb_number: string | null;
        hawb_number: string | null;
        airline_name: string | null;
        flight_number: string | null;
    } | null;
}

interface Props {
    shippingOrder: ShippingOrder;
    customers: Customer[];
    ports: Port[];
    transportModes: TransportMode[];
    serviceTypes: ServiceType[];
    currencies: Currency[];
    footerTerms: Term[];
}

// Helper function to format date for input[type=datetime-local]
function formatDateForInput(dateStr: string | null): string {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        return date.toISOString().slice(0, 16);
    } catch {
        return '';
    }
}

// Helper to detect transport mode type
function getShipmentType(code: string | undefined): 'ocean' | 'air' | null {
    if (!code) return null;
    const upperCode = code.toUpperCase();
    if (['OCEAN', 'SEA', 'FCL', 'LCL'].includes(upperCode)) return 'ocean';
    if (['AIR'].includes(upperCode)) return 'air';
    return null;
}

export default function ShippingOrderEdit({
    shippingOrder,
    customers,
    ports,
    transportModes,
    serviceTypes,
    currencies,
    footerTerms,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Órdenes de Envío', href: '/shipping-orders' },
        {
            title: shippingOrder.order_number,
            href: `/shipping-orders/${shippingOrder.id}`,
        },
        { title: 'Editar', href: `/shipping-orders/${shippingOrder.id}/edit` },
    ];

    // Modal state for mode change warning
    const [showModeChangeAlert, setShowModeChangeAlert] = useState(false);
    const [pendingModeId, setPendingModeId] = useState<string | null>(null);

    // useForm with initial values from shippingOrder
    const { data, setData, put, processing, errors } = useForm({
        // Base fields
        customer_id: shippingOrder.customer_id.toString(),
        contact_id: shippingOrder.contact_id?.toString() || '',
        shipper_id: shippingOrder.shipper_id,
        consignee_id: shippingOrder.consignee_id,
        origin_port_id: shippingOrder.origin_port_id.toString(),
        destination_port_id: shippingOrder.destination_port_id.toString(),
        transport_mode_id: shippingOrder.transport_mode_id.toString(),
        service_type_id: shippingOrder.service_type_id.toString(),
        currency_id: shippingOrder.currency_id.toString(),
        total_amount: shippingOrder.total_amount || '',
        total_pieces: shippingOrder.total_pieces?.toString() || '',
        total_weight_kg: shippingOrder.total_weight_kg || '',
        total_volume_cbm: shippingOrder.total_volume_cbm || '',
        planned_departure_at: formatDateForInput(
            shippingOrder.planned_departure_at,
        ),
        planned_arrival_at: formatDateForInput(
            shippingOrder.planned_arrival_at,
        ),
        pickup_date: shippingOrder.pickup_date || '',
        delivery_date: shippingOrder.delivery_date || '',
        notes: shippingOrder.notes || '',
        footer_terms_id: shippingOrder.footer_terms_id?.toString() || '',

        // Ocean shipment (nested)
        ocean_shipment: {
            mbl_number: shippingOrder.ocean_shipment?.mbl_number || '',
            hbl_number: shippingOrder.ocean_shipment?.hbl_number || '',
            carrier_name: shippingOrder.ocean_shipment?.carrier_name || '',
            vessel_name: shippingOrder.ocean_shipment?.vessel_name || '',
            voyage_number: shippingOrder.ocean_shipment?.voyage_number || '',
            container_details:
                shippingOrder.ocean_shipment?.container_details ||
                ([] as ContainerDetail[]),
        },

        // Air shipment (nested)
        air_shipment: {
            mawb_number: shippingOrder.air_shipment?.mawb_number || '',
            hawb_number: shippingOrder.air_shipment?.hawb_number || '',
            airline_name: shippingOrder.air_shipment?.airline_name || '',
            flight_number: shippingOrder.air_shipment?.flight_number || '',
        },
    });

    // Detect selected transport mode type
    const selectedTransportMode = useMemo(() => {
        if (!data.transport_mode_id) return null;
        const mode = transportModes.find(
            (m) => m.id.toString() === data.transport_mode_id,
        );
        return getShipmentType(mode?.code);
    }, [data.transport_mode_id, transportModes]);

    // Detect if shipment has existing data
    const hasOceanData = useMemo(() => {
        const os = data.ocean_shipment;
        return !!(
            os.mbl_number ||
            os.hbl_number ||
            os.carrier_name ||
            os.vessel_name ||
            os.voyage_number ||
            os.container_details.length > 0
        );
    }, [data.ocean_shipment]);

    const hasAirData = useMemo(() => {
        const as = data.air_shipment;
        return !!(
            as.mawb_number ||
            as.hawb_number ||
            as.airline_name ||
            as.flight_number
        );
    }, [data.air_shipment]);

    // Handle transport mode change with exclusivity check
    const handleTransportModeChange = (newModeId: string) => {
        const newMode = transportModes.find(
            (m) => m.id.toString() === newModeId,
        );
        const newType = getShipmentType(newMode?.code);

        // Check if switching would lose data
        if (
            (newType === 'ocean' && hasAirData) ||
            (newType === 'air' && hasOceanData) ||
            (newType === null && (hasOceanData || hasAirData))
        ) {
            setPendingModeId(newModeId);
            setShowModeChangeAlert(true);
            return;
        }

        setData('transport_mode_id', newModeId);
    };

    // Force mode change (clear shipment data)
    const handleForceModeChange = () => {
        if (pendingModeId) {
            // Clear shipment data based on what's being abandoned
            if (hasOceanData) {
                setData({
                    ...data,
                    transport_mode_id: pendingModeId,
                    ocean_shipment: {
                        mbl_number: '',
                        hbl_number: '',
                        carrier_name: '',
                        vessel_name: '',
                        voyage_number: '',
                        container_details: [],
                    },
                });
            } else if (hasAirData) {
                setData({
                    ...data,
                    transport_mode_id: pendingModeId,
                    air_shipment: {
                        mawb_number: '',
                        hawb_number: '',
                        airline_name: '',
                        flight_number: '',
                    },
                });
            }
        }
        setShowModeChangeAlert(false);
        setPendingModeId(null);
    };

    // Handle submit - send only active modality
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Null out inactive modality before submit by updating form data
        // Note: We need to update the form data to clear the unused shipment
        if (selectedTransportMode === 'ocean') {
            setData('air_shipment', {
                mawb_number: '',
                hawb_number: '',
                airline_name: '',
                flight_number: '',
            });
        } else if (selectedTransportMode === 'air') {
            setData('ocean_shipment', {
                mbl_number: '',
                hbl_number: '',
                carrier_name: '',
                vessel_name: '',
                voyage_number: '',
                container_details: [],
            });
        }

        // Use setTimeout to allow state update before put
        setTimeout(() => {
            put(`/shipping-orders/${shippingOrder.id}`);
        }, 0);
    };

    // Ocean shipment field change handler
    const handleOceanChange = (
        field: keyof OceanShipmentData,
        value: string | ContainerDetail[],
    ) => {
        setData('ocean_shipment', {
            ...data.ocean_shipment,
            [field]: value,
        });
    };

    // Air shipment field change handler
    const handleAirChange = (field: keyof AirShipmentData, value: string) => {
        setData('air_shipment', {
            ...data.air_shipment,
            [field]: value,
        });
    };

    // Create port options with formatted labels
    const portOptions = useMemo(
        () =>
            ports.map((port) => ({
                value: port.id.toString(),
                label: `${port.code} - ${port.name} (${port.country})`,
            })),
        [ports],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Orden de Envío" />

            <div className="mx-auto max-w-4xl space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/shipping-orders">
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-5 w-5" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="flex items-center gap-2 text-2xl font-bold">
                                <Package className="h-6 w-6" />
                                Editar {shippingOrder.order_number}
                            </h1>
                            <p className="text-muted-foreground">
                                Modificar los datos de la orden de envío
                            </p>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Main Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                            <CardDescription>
                                Datos principales de la orden de envío
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Customer */}
                            <div className="space-y-2">
                                <Label htmlFor="customer_id">Cliente *</Label>
                                <Select
                                    value={data.customer_id}
                                    onValueChange={(val) =>
                                        setData('customer_id', val)
                                    }
                                >
                                    <SelectTrigger
                                        className={
                                            errors.customer_id
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar cliente" />
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
                                {errors.customer_id && (
                                    <p className="text-sm text-destructive">
                                        {errors.customer_id}
                                    </p>
                                )}
                            </div>

                            {/* Ports */}
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="origin_port_id">
                                        Puerto de Origen *
                                    </Label>
                                    <Select
                                        value={data.origin_port_id}
                                        onValueChange={(val) =>
                                            setData('origin_port_id', val)
                                        }
                                    >
                                        <SelectTrigger
                                            className={
                                                errors.origin_port_id
                                                    ? 'border-destructive'
                                                    : ''
                                            }
                                        >
                                            <SelectValue placeholder="Seleccionar origen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {portOptions.map((p) => (
                                                <SelectItem
                                                    key={p.value}
                                                    value={p.value}
                                                >
                                                    {p.label}
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

                                <div className="space-y-2">
                                    <Label htmlFor="destination_port_id">
                                        Puerto de Destino *
                                    </Label>
                                    <Select
                                        value={data.destination_port_id}
                                        onValueChange={(val) =>
                                            setData('destination_port_id', val)
                                        }
                                    >
                                        <SelectTrigger
                                            className={
                                                errors.destination_port_id
                                                    ? 'border-destructive'
                                                    : ''
                                            }
                                        >
                                            <SelectValue placeholder="Seleccionar destino" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {portOptions.map((p) => (
                                                <SelectItem
                                                    key={p.value}
                                                    value={p.value}
                                                >
                                                    {p.label}
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
                            </div>

                            {/* Transport, Service, Currency */}
                            <div className="grid gap-4 md:grid-cols-3">
                                <div className="space-y-2">
                                    <Label htmlFor="transport_mode_id">
                                        Modo de Transporte *
                                    </Label>
                                    <Select
                                        value={data.transport_mode_id}
                                        onValueChange={
                                            handleTransportModeChange
                                        }
                                    >
                                        <SelectTrigger
                                            className={
                                                errors.transport_mode_id
                                                    ? 'border-destructive'
                                                    : ''
                                            }
                                        >
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {transportModes.map((m) => (
                                                <SelectItem
                                                    key={m.id}
                                                    value={m.id.toString()}
                                                >
                                                    {m.code} - {m.name}
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

                                <div className="space-y-2">
                                    <Label htmlFor="service_type_id">
                                        Tipo de Servicio *
                                    </Label>
                                    <Select
                                        value={data.service_type_id}
                                        onValueChange={(val) =>
                                            setData('service_type_id', val)
                                        }
                                    >
                                        <SelectTrigger
                                            className={
                                                errors.service_type_id
                                                    ? 'border-destructive'
                                                    : ''
                                            }
                                        >
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {serviceTypes.map((s) => (
                                                <SelectItem
                                                    key={s.id}
                                                    value={s.id.toString()}
                                                >
                                                    {s.code} - {s.name}
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

                                <div className="space-y-2">
                                    <Label htmlFor="currency_id">
                                        Moneda *
                                    </Label>
                                    <Select
                                        value={data.currency_id}
                                        onValueChange={(val) =>
                                            setData('currency_id', val)
                                        }
                                    >
                                        <SelectTrigger
                                            className={
                                                errors.currency_id
                                                    ? 'border-destructive'
                                                    : ''
                                            }
                                        >
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((c) => (
                                                <SelectItem
                                                    key={c.id}
                                                    value={c.id.toString()}
                                                >
                                                    {c.code} ({c.symbol})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.currency_id && (
                                        <p className="text-sm text-destructive">
                                            {errors.currency_id}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Parties Section (Shipper/Consignee) */}
                    <PartiesSection
                        customers={customers}
                        shipperId={data.shipper_id}
                        consigneeId={data.consignee_id}
                        onShipperChange={(id) => setData('shipper_id', id)}
                        onConsigneeChange={(id) => setData('consignee_id', id)}
                        errors={errors}
                    />

                    {/* Dynamic Shipment Details Section */}
                    {selectedTransportMode === 'ocean' && (
                        <OceanShipmentForm
                            data={data.ocean_shipment}
                            onChange={handleOceanChange}
                            errors={errors}
                        />
                    )}

                    {selectedTransportMode === 'air' && (
                        <AirShipmentForm
                            data={data.air_shipment}
                            onChange={handleAirChange}
                            errors={errors}
                        />
                    )}

                    {/* Dates */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Fechas</CardTitle>
                            <CardDescription>
                                Fechas planificadas de salida y llegada
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="planned_departure_at">
                                        Salida Planificada
                                    </Label>
                                    <Input
                                        id="planned_departure_at"
                                        type="datetime-local"
                                        value={data.planned_departure_at}
                                        onChange={(e) =>
                                            setData(
                                                'planned_departure_at',
                                                e.target.value,
                                            )
                                        }
                                        className={
                                            errors.planned_departure_at
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                    {errors.planned_departure_at && (
                                        <p className="text-sm text-destructive">
                                            {errors.planned_departure_at}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="planned_arrival_at">
                                        Llegada Planificada
                                    </Label>
                                    <Input
                                        id="planned_arrival_at"
                                        type="datetime-local"
                                        value={data.planned_arrival_at}
                                        onChange={(e) =>
                                            setData(
                                                'planned_arrival_at',
                                                e.target.value,
                                            )
                                        }
                                        className={
                                            errors.planned_arrival_at
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                    {errors.planned_arrival_at && (
                                        <p className="text-sm text-destructive">
                                            {errors.planned_arrival_at}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Cargo */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Carga (Opcional)</CardTitle>
                            <CardDescription>
                                Detalles de la mercancía
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-4">
                                <div className="space-y-2">
                                    <Label htmlFor="total_pieces">Piezas</Label>
                                    <Input
                                        id="total_pieces"
                                        type="number"
                                        min="0"
                                        value={data.total_pieces}
                                        onChange={(e) =>
                                            setData(
                                                'total_pieces',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="total_weight_kg">
                                        Peso (Kg)
                                    </Label>
                                    <Input
                                        id="total_weight_kg"
                                        type="number"
                                        min="0"
                                        step="0.001"
                                        value={data.total_weight_kg}
                                        onChange={(e) =>
                                            setData(
                                                'total_weight_kg',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="total_volume_cbm">
                                        Volumen (CBM)
                                    </Label>
                                    <Input
                                        id="total_volume_cbm"
                                        type="number"
                                        min="0"
                                        step="0.001"
                                        value={data.total_volume_cbm}
                                        onChange={(e) =>
                                            setData(
                                                'total_volume_cbm',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="total_amount">
                                        Monto Total
                                    </Label>
                                    <Input
                                        id="total_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.total_amount}
                                        onChange={(e) =>
                                            setData(
                                                'total_amount',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Terms and Notes */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Términos y Notas</CardTitle>
                            <CardDescription>
                                Condiciones especiales y observaciones
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="footer_terms_id">
                                    Términos y Condiciones
                                </Label>
                                <Select
                                    value={data.footer_terms_id}
                                    onValueChange={(val) =>
                                        setData('footer_terms_id', val)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar términos (opcional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {footerTerms.map((t) => (
                                            <SelectItem
                                                key={t.id}
                                                value={t.id.toString()}
                                            >
                                                {t.code} - {t.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="notes">Notas</Label>
                                <Textarea
                                    id="notes"
                                    placeholder="Observaciones adicionales..."
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit */}
                    <div className="flex justify-end gap-4">
                        <Link href={`/shipping-orders/${shippingOrder.id}`}>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Guardar Cambios'}
                        </Button>
                    </div>
                </form>
            </div>

            {/* Mode Change Alert Dialog */}
            <AlertDialog
                open={showModeChangeAlert}
                onOpenChange={setShowModeChangeAlert}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-amber-500" />
                            Cambio de Modalidad
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Ya existen detalles de embarque ingresados. Si
                            cambia el modo de transporte, estos datos se
                            perderán.
                            <br />
                            <br />
                            ¿Desea continuar y eliminar los detalles actuales?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel
                            onClick={() => setPendingModeId(null)}
                        >
                            Cancelar
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleForceModeChange}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Sí, cambiar modo
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
