import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Truck } from 'lucide-react';
import { useMemo } from 'react';

interface Driver {
    id: number;
    name: string;
}

interface Customer {
    id: number;
    name: string;
    code: string;
}

interface ShippingOrder {
    id: number;
    order_number: string;
    customer_id: number;
}

interface Props {
    customers: Customer[];
    drivers: Driver[];
    shippingOrders: ShippingOrder[];
    shippingOrderId?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Recogidas', href: '/pickup-orders' },
    { title: 'Nueva', href: '/pickup-orders/create' },
];

export default function PickupOrderCreate({
    customers,
    drivers,
    shippingOrders,
    shippingOrderId,
}: Props) {
    const { data, setData, processing, errors } = useForm({
        customer_id: '',
        shipping_order_id: shippingOrderId || '',
        driver_id: '',
        reference: '',
        scheduled_date: '',
        notes: '',
    });

    // Filter shipping orders by selected customer
    const filteredShippingOrders = useMemo(() => {
        if (!data.customer_id) return [];
        return shippingOrders.filter(
            (so) => so.customer_id === parseInt(data.customer_id),
        );
    }, [shippingOrders, data.customer_id]);

    // Handle customer change - reset shipping_order_id if not valid for new customer
    const handleCustomerChange = (customerId: string) => {
        setData((prev) => {
            const newShippingOrderId = shippingOrders.find(
                (so) =>
                    so.id.toString() === prev.shipping_order_id &&
                    so.customer_id === parseInt(customerId),
            )
                ? prev.shipping_order_id
                : '';
            return {
                ...prev,
                customer_id: customerId,
                shipping_order_id: newShippingOrderId,
            };
        });
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // Clean 'none' placeholder values to empty strings for nullable fields
        router.post('/pickup-orders', {
            ...data,
            shipping_order_id:
                data.shipping_order_id === 'none' ? '' : data.shipping_order_id,
            driver_id: data.driver_id === 'none' ? '' : data.driver_id,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Recogida" />

            <form onSubmit={handleSubmit} className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/pickup-orders">
                            <Button variant="ghost" size="icon" type="button">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                <Truck className="h-5 w-5 text-primary" />
                            </div>
                            <h1 className="text-2xl font-bold">
                                Nueva Recogida
                            </h1>
                        </div>
                    </div>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Guardando...' : 'Guardar'}
                    </Button>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Main Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información Principal</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>Cliente *</Label>
                                <Select
                                    value={data.customer_id}
                                    onValueChange={handleCustomerChange}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Seleccionar cliente..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {customers.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={c.id.toString()}
                                            >
                                                {c.code} - {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.customer_id && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.customer_id}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Orden de Envío (opcional)</Label>
                                <Select
                                    value={data.shipping_order_id}
                                    onValueChange={(v) =>
                                        setData('shipping_order_id', v)
                                    }
                                    disabled={!data.customer_id}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue
                                            placeholder={
                                                data.customer_id
                                                    ? 'Sin vincular'
                                                    : 'Seleccione cliente primero'
                                            }
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Sin vincular
                                        </SelectItem>
                                        {filteredShippingOrders.map((so) => (
                                            <SelectItem
                                                key={so.id}
                                                value={so.id.toString()}
                                            >
                                                {so.order_number}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {filteredShippingOrders.length === 0 &&
                                    data.customer_id && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Este cliente no tiene órdenes de
                                            envío
                                        </p>
                                    )}
                            </div>

                            <div>
                                <Label>Referencia</Label>
                                <Input
                                    className="mt-1"
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData('reference', e.target.value)
                                    }
                                    placeholder="Referencia interna..."
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Scheduling */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Programación</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>Fecha Programada</Label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={data.scheduled_date}
                                    onChange={(e) =>
                                        setData(
                                            'scheduled_date',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div>
                                <Label>Conductor (opcional)</Label>
                                <Select
                                    value={data.driver_id}
                                    onValueChange={(v) =>
                                        setData('driver_id', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Asignar después" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">
                                            Asignar después
                                        </SelectItem>
                                        {drivers.map((d) => (
                                            <SelectItem
                                                key={d.id}
                                                value={d.id.toString()}
                                            >
                                                {d.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label>Notas</Label>
                                <Textarea
                                    className="mt-1"
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                    placeholder="Instrucciones especiales..."
                                    rows={4}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </form>
        </AppLayout>
    );
}
