import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface ReceiptLine {
    id?: number;
    sku: string;
    description: string;
    expected_qty: string;
    received_qty: string;
    uom: string;
    lot_number: string;
    serial_number: string;
    expiration_date: string;
}

interface Receipt {
    id: number;
    receipt_number: string | null;
    reference: string | null;
    expected_at: string | null;
    notes: string | null;
    warehouse_id: number;
    customer_id: number;
    warehouse: Warehouse;
    customer: Customer;
    lines: ReceiptLine[];
}

interface Props {
    receipt: Receipt;
}

const emptyLine: ReceiptLine = {
    sku: '',
    description: '',
    expected_qty: '',
    received_qty: '0',
    uom: 'PCS',
    lot_number: '',
    serial_number: '',
    expiration_date: '',
};

export default function WarehouseReceiptEdit({ receipt }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inicio', href: '/dashboard' },
        { title: 'Recepciones', href: '/warehouse-receipts' },
        {
            title: receipt.receipt_number || `#${receipt.id}`,
            href: `/warehouse-receipts/${receipt.id}`,
        },
        { title: 'Editar', href: `/warehouse-receipts/${receipt.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        reference: receipt.reference || '',
        expected_at: receipt.expected_at?.split('T')[0] || '',
        notes: receipt.notes || '',
        lines: receipt.lines.map((l) => ({
            id: l.id,
            sku: l.sku,
            description: l.description || '',
            expected_qty: l.expected_qty?.toString() || '',
            received_qty: l.received_qty.toString(),
            uom: l.uom,
            lot_number: l.lot_number || '',
            serial_number: l.serial_number || '',
            expiration_date: l.expiration_date?.split('T')[0] || '',
        })) as ReceiptLine[],
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
        field: keyof ReceiptLine,
        value: string,
    ) => {
        const newLines = [...data.lines];
        newLines[index] = { ...newLines[index], [field]: value };
        setData('lines', newLines);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/warehouse-receipts/${receipt.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${receipt.receipt_number || receipt.id}`} />

            <form onSubmit={handleSubmit} className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={`/warehouse-receipts/${receipt.id}`}>
                            <Button type="button" variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <h1 className="text-2xl font-bold">
                            Editar {receipt.receipt_number || `#${receipt.id}`}
                        </h1>
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
                                <Label>Almacén</Label>
                                <p className="mt-1 font-medium">
                                    {receipt.warehouse.name} (
                                    {receipt.warehouse.code})
                                </p>
                            </div>
                            <div>
                                <Label>Cliente</Label>
                                <p className="mt-1 font-medium">
                                    {receipt.customer.name}
                                </p>
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
                            {Object.keys(errors).length > 0 && (
                                <div className="md:col-span-2 lg:col-span-4">
                                    <p className="text-sm text-red-500">
                                        {Object.values(errors)
                                            .flat()
                                            .join(', ')}
                                    </p>
                                </div>
                            )}
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
                                    <TableHead className="w-32">
                                        SKU *
                                    </TableHead>
                                    <TableHead className="w-48">
                                        Descripción
                                    </TableHead>
                                    <TableHead className="w-24">
                                        Esperado
                                    </TableHead>
                                    <TableHead className="w-24">
                                        Recibido *
                                    </TableHead>
                                    <TableHead className="w-20">UOM</TableHead>
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
                                            <Input
                                                value={line.sku}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'sku',
                                                        e.target.value,
                                                    )
                                                }
                                            />
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
                                            />
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
