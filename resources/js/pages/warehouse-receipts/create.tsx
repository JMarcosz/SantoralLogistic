import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    CustomerItemCombobox,
    CustomerItemResult,
} from '@/components/ui/customer-item-combobox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface Customer {
    id: number;
    name: string;
}

interface LineData {
    item_code: string;
    description: string;
    expected_qty: string;
    received_qty: string;
    uom: string;
    lot_number: string;
    serial_number: string;
    expiration_date: string;
}

interface Props {
    warehouses: Warehouse[];
    customers: Customer[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Inicio', href: '/dashboard' },
    { title: 'Recepciones', href: '/warehouse-receipts' },
    { title: 'Nueva Recepción', href: '/warehouse-receipts/create' },
];

const emptyLine: LineData = {
    item_code: '',
    description: '',
    expected_qty: '',
    received_qty: '0',
    uom: 'PCS',
    lot_number: '',
    serial_number: '',
    expiration_date: '',
};

export default function WarehouseReceiptCreate({
    warehouses,
    customers,
}: Props) {
    // Note: auth prop might be needed if layout requires it, usually AppLayout handles it contextually or via props
    const { data, setData, post, processing, errors } = useForm({
        warehouse_id: '',
        customer_id: '',
        reference: '',
        expected_at: '',
        notes: '',
        lines: [{ ...emptyLine }] as LineData[],
    });

    const addLine = () => {
        setData('lines', [...data.lines, { ...emptyLine }]);
    };

    const removeLine = (index: number) => {
        if (data.lines.length > 1) {
            setData(
                'lines',
                data.lines.filter((_, i) => i !== index),
            );
        }
    };

    const updateLine = (
        index: number,
        field: keyof LineData,
        value: string,
    ) => {
        const newLines = [...data.lines];
        newLines[index] = { ...newLines[index], [field]: value };
        setData('lines', newLines);
    };

    const handleItemSelect = (index: number, result: CustomerItemResult) => {
        const newLines = [...data.lines];
        newLines[index] = {
            ...newLines[index],
            item_code: result.code || '',
            description: result.description,
            uom: result.uom || 'PCS',
        };
        setData('lines', newLines);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/warehouse-receipts');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Recepción" />

            <form onSubmit={handleSubmit} className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/warehouse-receipts">
                            <Button type="button" variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <h1 className="text-2xl font-bold">Nueva Recepción</h1>
                    </div>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Guardando...' : 'Guardar'}
                    </Button>
                </div>

                {/* Receipt Details */}
                <Card>
                    <CardHeader>
                        <CardTitle>Información de la Recepción</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <Label htmlFor="warehouse_id">Almacén *</Label>
                                <Select
                                    value={data.warehouse_id}
                                    onValueChange={(v) =>
                                        setData('warehouse_id', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Seleccionar..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {warehouses.map((w) => (
                                            <SelectItem
                                                key={w.id}
                                                value={w.id.toString()}
                                            >
                                                {w.name} ({w.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.warehouse_id && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.warehouse_id}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="customer_id">Cliente *</Label>
                                <Select
                                    value={data.customer_id}
                                    onValueChange={(v) =>
                                        setData('customer_id', v)
                                    }
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Seleccionar..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {customers.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={c.id.toString()}
                                            >
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.customer_id && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.customer_id}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="reference">Referencia</Label>
                                <Input
                                    className="mt-1"
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData('reference', e.target.value)
                                    }
                                    placeholder="PO, SO, etc."
                                />
                            </div>
                            <div>
                                <Label htmlFor="expected_at">
                                    Fecha Esperada
                                </Label>
                                <Input
                                    type="date"
                                    className="mt-1"
                                    value={data.expected_at}
                                    onChange={(e) =>
                                        setData('expected_at', e.target.value)
                                    }
                                />
                            </div>
                            <div className="md:col-span-2 lg:col-span-4">
                                <Label htmlFor="notes">Notas</Label>
                                <Textarea
                                    className="mt-1"
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                    rows={2}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Lines */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Líneas de Recepción</CardTitle>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addLine}
                        >
                            <Plus className="mr-1 h-4 w-4" />
                            Agregar Línea
                        </Button>
                    </CardHeader>
                    <CardContent className="overflow-x-auto p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-40">
                                        Item / Referencia
                                    </TableHead>
                                    <TableHead className="w-48">
                                        Descripción *
                                    </TableHead>
                                    <TableHead className="w-24">
                                        Esperado
                                    </TableHead>
                                    <TableHead className="w-24">
                                        Recibido *
                                    </TableHead>
                                    <TableHead className="w-20">
                                        UOM *
                                    </TableHead>
                                    <TableHead className="w-28">Lote</TableHead>
                                    <TableHead className="w-28">
                                        Serial
                                    </TableHead>
                                    <TableHead className="w-32">
                                        Vencimiento
                                    </TableHead>
                                    <TableHead className="w-12"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.lines.map((line, index) => (
                                    <TableRow key={index}>
                                        <TableCell>
                                            <CustomerItemCombobox
                                                customerId={
                                                    data.customer_id
                                                        ? parseInt(
                                                              data.customer_id,
                                                          )
                                                        : null
                                                }
                                                value={line.item_code}
                                                onChange={(val) =>
                                                    updateLine(
                                                        index,
                                                        'item_code',
                                                        val,
                                                    )
                                                }
                                                onSelect={(result) =>
                                                    handleItemSelect(
                                                        index,
                                                        result,
                                                    )
                                                }
                                                placeholder={
                                                    data.customer_id
                                                        ? 'Buscar item...'
                                                        : 'Seleccione cliente'
                                                }
                                            />
                                            {/* Show error for item_code if any */}
                                            {errors[
                                                `lines.${index}.item_code`
                                            ] && (
                                                <p className="mt-1 text-xs text-red-500">
                                                    {
                                                        errors[
                                                            `lines.${index}.item_code`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={line.description}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'description',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Descripción requerida"
                                                required
                                            />
                                            {errors[
                                                `lines.${index}.description`
                                            ] && (
                                                <p className="mt-1 text-xs text-red-500">
                                                    {
                                                        errors[
                                                            `lines.${index}.description`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                value={line.expected_qty}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'expected_qty',
                                                        e.target.value,
                                                    )
                                                }
                                                min="0"
                                                step="0.001"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                value={line.received_qty}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'received_qty',
                                                        e.target.value,
                                                    )
                                                }
                                                min="0"
                                                step="0.001"
                                                required
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={line.uom}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'uom',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="PCS"
                                                required
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={line.lot_number}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'lot_number',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={line.serial_number}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'serial_number',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="date"
                                                value={line.expiration_date}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'expiration_date',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {data.lines.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        removeLine(index)
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </form>
        </AppLayout>
    );
}
