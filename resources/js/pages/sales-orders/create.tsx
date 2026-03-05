/* eslint-disable @typescript-eslint/no-explicit-any */
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
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
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Currency, type ProductService } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import {
    Check,
    ChevronsUpDown,
    Package,
    Plus,
    Save,
    Settings,
    Trash2,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

interface Customer {
    id: number;
    name: string;
    code: string;
}

interface Props {
    customers: Customer[];
    currencies: Currency[];
    productsServices: ProductService[];
}

interface FormLine {
    product_service_id: number | '';
    line_type: 'product' | 'service';
    description: string;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_rate: number;
}

type LineTab = 'product' | 'service';

function ProductServiceCombobox({
    value,
    items,
    onChange,
    placeholder = 'Buscar...',
}: {
    value: number | '';
    items: ProductService[];
    onChange: (id: number) => void;
    placeholder?: string;
}) {
    const [open, setOpen] = useState(false);
    const selected = items.find((i) => i.id === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between font-normal"
                >
                    <span className="truncate">
                        {selected ? `${selected.code} — ${selected.name}` : placeholder}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[320px] p-0" align="start">
                <Command>
                    <CommandInput placeholder="Buscar por código o nombre..." />
                    <CommandList>
                        <CommandEmpty>Sin resultados</CommandEmpty>
                        <CommandGroup>
                            {items.map((item) => (
                                <CommandItem
                                    key={item.id}
                                    value={`${item.code} ${item.name}`}
                                    onSelect={() => {
                                        onChange(item.id);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 h-4 w-4',
                                            value === item.id ? 'opacity-100' : 'opacity-0',
                                        )}
                                    />
                                    <div className="flex flex-col">
                                        <span className="font-medium">{item.code}</span>
                                        <span className="text-xs text-muted-foreground">{item.name}</span>
                                    </div>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Órdenes de Pedido', href: '/sales-orders' },
    { title: 'Nueva Orden', href: '/sales-orders/create' },
];

export default function SalesOrderCreate({
    customers,
    currencies,
    productsServices,
}: Props) {
    const [activeLineTab, setActiveLineTab] = useState<LineTab>('service');

    const { data, setData, post, processing, errors } = useForm({
        customer_id: '' as number | '',
        currency_id: currencies.find((c) => c.code === 'USD')?.id ?? ('' as number | ''),
        notes: '',
        lines: [
            {
                product_service_id: '' as number | '',
                line_type: 'service' as 'product' | 'service',
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_percent: 0,
                tax_rate: 18,
            },
        ] as FormLine[],
    });

    const products = useMemo(
        () => productsServices.filter((p) => p.type === 'product'),
        [productsServices],
    );
    const services = useMemo(
        () => productsServices.filter((p) => p.type === 'service' || p.type === 'fee'),
        [productsServices],
    );

    const filteredLineIndices = useMemo(() => {
        return data.lines
            .map((line, index) => ({ line, index }))
            .filter(({ line }) => (line.line_type || 'service') === activeLineTab);
    }, [data.lines, activeLineTab]);

    const activeItems = activeLineTab === 'product' ? products : services;

    const addLine = useCallback(() => {
        setData('lines', [
            ...data.lines,
            {
                product_service_id: '' as number | '',
                line_type: activeLineTab,
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_percent: 0,
                tax_rate: 18,
            },
        ]);
    }, [data.lines, setData, activeLineTab]);

    const removeLine = useCallback(
        (index: number) => {
            if (data.lines.length > 1) {
                setData('lines', data.lines.filter((_, i) => i !== index));
            }
        },
        [data.lines, setData],
    );

    const updateLine = useCallback(
        (index: number, field: keyof FormLine, value: any) => {
            const newLines = [...data.lines];
            newLines[index] = { ...newLines[index], [field]: value };

            if (field === 'product_service_id' && typeof value === 'number') {
                const product = productsServices.find((p) => p.id === value);
                if (product) {
                    newLines[index].unit_price = product.default_unit_price ?? 0;
                    newLines[index].tax_rate = product.taxable ? 18 : 0;
                    newLines[index].description = product.name;
                    newLines[index].line_type = product.type === 'product' ? 'product' : 'service';
                }
            }

            setData('lines', newLines);
        },
        [data.lines, setData, productsServices],
    );

    const calculateLineTotal = useCallback((line: FormLine) => {
        const subtotal = line.quantity * line.unit_price;
        const discount = subtotal * (line.discount_percent / 100);
        return subtotal - discount;
    }, []);

    const totals = useMemo(() => {
        let subtotal = 0;
        let taxAmount = 0;
        data.lines.forEach((line) => {
            const lineNet = calculateLineTotal(line);
            const lineTax = lineNet * (line.tax_rate / 100);
            subtotal += lineNet;
            taxAmount += lineTax;
        });
        return { subtotal, taxAmount, total: subtotal + taxAmount };
    }, [data.lines, calculateLineTotal]);

    const currencySymbol = useMemo(() => {
        const currency = currencies.find((c) => c.id === Number(data.currency_id));
        return currency?.symbol ?? '$';
    }, [currencies, data.currency_id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/sales-orders');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Orden de Pedido" />

            <form onSubmit={handleSubmit} className="mx-auto max-w-6xl space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight">
                        Nueva Orden de Pedido
                    </h1>
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.history.back()}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            Guardar Orden
                        </Button>
                    </div>
                </div>

                {/* General Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Información General</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Cliente *</Label>
                                <Select
                                    value={String(data.customer_id)}
                                    onValueChange={(v) => setData('customer_id', Number(v))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar cliente" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {customers.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name} {c.code ? `(${c.code})` : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.customer_id && (
                                    <p className="text-sm text-destructive">{errors.customer_id}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Moneda *</Label>
                                <Select
                                    value={String(data.currency_id)}
                                    onValueChange={(v) => setData('currency_id', Number(v))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar moneda" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {currencies.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.code} ({c.symbol})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.currency_id && (
                                    <p className="text-sm text-destructive">{errors.currency_id}</p>
                                )}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label>Notas</Label>
                                <Textarea
                                    rows={3}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Notas internas"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Lines */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Líneas de Pedido</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {/* Product / Service Sub-Tabs */}
                        <div className="mb-4 flex items-center gap-1 rounded-lg border bg-muted/30 p-1">
                            <button
                                type="button"
                                onClick={() => setActiveLineTab('product')}
                                className={cn(
                                    'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all',
                                    activeLineTab === 'product'
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted',
                                )}
                            >
                                <Package className="h-4 w-4" />
                                Productos
                                <span className="ml-1 rounded-full bg-background/20 px-1.5 py-0.5 text-xs">
                                    {data.lines.filter((l) => l.line_type === 'product').length}
                                </span>
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveLineTab('service')}
                                className={cn(
                                    'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all',
                                    activeLineTab === 'service'
                                        ? 'bg-primary text-primary-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted',
                                )}
                            >
                                <Settings className="h-4 w-4" />
                                Servicios
                                <span className="ml-1 rounded-full bg-background/20 px-1.5 py-0.5 text-xs">
                                    {data.lines.filter((l) => l.line_type === 'service').length}
                                </span>
                            </button>

                            <div className="ml-auto">
                                <Button type="button" variant="outline" size="sm" onClick={addLine}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar {activeLineTab === 'product' ? 'Producto' : 'Servicio'}
                                </Button>
                            </div>
                        </div>

                        {errors.lines && (
                            <p className="mb-4 text-sm text-destructive">{errors.lines}</p>
                        )}

                        {filteredLineIndices.length === 0 ? (
                            <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-muted-foreground">
                                {activeLineTab === 'product' ? (
                                    <Package className="mb-2 h-8 w-8" />
                                ) : (
                                    <Settings className="mb-2 h-8 w-8" />
                                )}
                                <p className="text-sm">
                                    No hay {activeLineTab === 'product' ? 'productos' : 'servicios'} agregados.
                                </p>
                                <Button type="button" variant="link" size="sm" onClick={addLine} className="mt-2">
                                    <Plus className="mr-1 h-3 w-3" />
                                    Agregar {activeLineTab === 'product' ? 'un producto' : 'un servicio'}
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[280px]">
                                                {activeLineTab === 'product' ? 'Producto' : 'Servicio'} *
                                            </TableHead>
                                            <TableHead className="w-[200px]">Descripción</TableHead>
                                            <TableHead className="w-[100px]">Cantidad *</TableHead>
                                            <TableHead className="w-[120px]">Precio Unit. *</TableHead>
                                            <TableHead className="w-[80px]">Desc. %</TableHead>
                                            <TableHead className="w-[80px]">ITBIS %</TableHead>
                                            <TableHead className="w-[120px] text-right">Total</TableHead>
                                            <TableHead className="w-[60px]"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {filteredLineIndices.map(({ line, index }) => (
                                            <TableRow key={index}>
                                                <TableCell>
                                                    <ProductServiceCombobox
                                                        value={line.product_service_id}
                                                        items={activeItems}
                                                        onChange={(id) => updateLine(index, 'product_service_id', id)}
                                                        placeholder={activeLineTab === 'product' ? 'Seleccionar producto...' : 'Seleccionar servicio...'}
                                                    />
                                                    {errors[`lines.${index}.product_service_id`] && (
                                                        <p className="text-xs text-destructive">{errors[`lines.${index}.product_service_id`]}</p>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        value={line.description}
                                                        onChange={(e) => updateLine(index, 'description', e.target.value)}
                                                        placeholder="Descripción"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0.0001"
                                                        step="0.0001"
                                                        value={line.quantity}
                                                        onChange={(e) => updateLine(index, 'quantity', parseFloat(e.target.value) || 0)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={line.unit_price}
                                                        onChange={(e) => updateLine(index, 'unit_price', parseFloat(e.target.value) || 0)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        value={line.discount_percent}
                                                        onChange={(e) => updateLine(index, 'discount_percent', parseFloat(e.target.value) || 0)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        value={line.tax_rate}
                                                        onChange={(e) => updateLine(index, 'tax_rate', parseFloat(e.target.value) || 0)}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right font-mono font-semibold">
                                                    {currencySymbol}
                                                    {calculateLineTotal(line).toLocaleString('en-US', { minimumFractionDigits: 2 })}
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => removeLine(index)}
                                                        disabled={data.lines.length <= 1}
                                                    >
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        {/* Totals */}
                        <div className="mt-6 flex justify-end">
                            <div className="w-64 space-y-2 rounded-lg border bg-muted/50 p-4">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Subtotal:</span>
                                    <span className="font-mono">
                                        {currencySymbol}{totals.subtotal.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                                    </span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">ITBIS:</span>
                                    <span className="font-mono">
                                        {currencySymbol}{totals.taxAmount.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                                    </span>
                                </div>
                                <div className="border-t pt-2">
                                    <div className="flex justify-between text-lg font-bold">
                                        <span>Total:</span>
                                        <span className="font-mono text-primary">
                                            {currencySymbol}{totals.total.toLocaleString('en-US', { minimumFractionDigits: 2 })}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end gap-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => window.history.back()}
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        Guardar Orden
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
