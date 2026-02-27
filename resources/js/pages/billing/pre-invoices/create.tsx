/* eslint-disable react-hooks/purity */
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import preInvoiceRoutes from '@/routes/pre-invoices';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Customer {
    id: number;
    name: string;
    code: string | null;
    tax_id: string | null;
}

interface Currency {
    id: number;
    code: string;
    symbol: string;
    name: string;
}

interface LineItem {
    code: string;
    description: string;
    qty: string;
    unit_price: string;
    tax_amount: string;
    is_taxable: boolean;
    tax_rate: string;
}

interface Props {
    customers: Customer[];
    currencies: Currency[];
    defaultCurrencyId: number | null;
}

export default function PreInvoiceCreate({
    customers,
    currencies,
    defaultCurrencyId,
}: Props) {
    const { flash } = usePage().props as { flash?: { error?: string } };
    const defaultCurrency = currencies.find((c) => c.id === defaultCurrencyId);

    const [isSubmitting, setIsSubmitting] = useState(false);
    const [form, setForm] = useState({
        customer_id: '',
        currency_code: defaultCurrency?.code || 'USD',
        issue_date: new Date().toISOString().slice(0, 10),
        due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
            .toISOString()
            .slice(0, 10),
        notes: '',
        external_ref: '',
    });

    const [lines, setLines] = useState<LineItem[]>([
        {
            code: '',
            description: '',
            qty: '1',
            unit_price: '0',
            tax_amount: '0',
            is_taxable: true,
            tax_rate: '0.18',
        },
    ]);

    const addLine = () => {
        setLines([
            ...lines,
            {
                code: '',
                description: '',
                qty: '1',
                unit_price: '0',
                tax_amount: '0',
                is_taxable: true,
                tax_rate: '0.18',
            },
        ]);
    };

    const removeLine = (index: number) => {
        if (lines.length > 1) {
            setLines(lines.filter((_, i) => i !== index));
        }
    };

    const updateLine = (
        index: number,
        field: keyof LineItem,
        value: string | boolean,
    ) => {
        const updated = [...lines];
        const line = { ...updated[index], [field]: value };

        // Auto-calculate tax_amount
        const qty = parseFloat(line.qty || '0');
        const unitPrice = parseFloat(line.unit_price || '0');
        const isTaxable = line.is_taxable;
        const taxRate = parseFloat(line.tax_rate || '0.18');

        if (isTaxable) {
            line.tax_amount = (qty * unitPrice * taxRate).toFixed(2);
        } else {
            line.tax_amount = '0';
        }

        updated[index] = line;
        setLines(updated);
    };

    const calculateTotals = () => {
        let subtotal = 0;
        let tax = 0;
        lines.forEach((line) => {
            const lineAmount =
                parseFloat(line.qty || '0') *
                parseFloat(line.unit_price || '0');
            subtotal += lineAmount;
            tax += parseFloat(line.tax_amount || '0');
        });
        return { subtotal, tax, total: subtotal + tax };
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const data = {
            ...form,
            customer_id: parseInt(form.customer_id),
            lines: lines.map((line) => ({
                code: line.code,
                description: line.description,
                qty: parseFloat(line.qty || '0'),
                unit_price: parseFloat(line.unit_price || '0'),
                tax_amount: parseFloat(line.tax_amount || '0'),
                is_taxable: line.is_taxable,
                tax_rate: parseFloat(line.tax_rate),
            })),
        };

        router.post(preInvoiceRoutes.store().url, data, {
            onFinish: () => setIsSubmitting(false),
        });
    };

    const totals = calculateTotals();
    const currencySymbol =
        currencies.find((c) => c.code === form.currency_code)?.symbol || '$';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pre-Facturas', href: preInvoiceRoutes.index().url },
                { title: 'Nueva', href: '#' },
            ]}
        >
            <Head title="Nueva Pre-Factura" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={preInvoiceRoutes.index().url}>
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Nueva Pre-Factura
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Crear una pre-factura manualmente
                        </p>
                    </div>
                </div>

                {flash?.error && (
                    <div className="rounded-lg border border-red-500/20 bg-red-500/10 p-4 text-red-500">
                        {flash.error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Main Form */}
                        <div className="space-y-6 lg:col-span-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Información General</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="customer_id">
                                                Cliente *
                                            </Label>
                                            <Select
                                                value={form.customer_id}
                                                onValueChange={(value) =>
                                                    setForm({
                                                        ...form,
                                                        customer_id: value,
                                                    })
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar cliente" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {customers.map(
                                                        (customer) => (
                                                            <SelectItem
                                                                key={
                                                                    customer.id
                                                                }
                                                                value={customer.id.toString()}
                                                            >
                                                                {customer.name}
                                                                {customer.code &&
                                                                    ` (${customer.code})`}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="currency_code">
                                                Moneda *
                                            </Label>
                                            <Select
                                                value={form.currency_code}
                                                onValueChange={(value) =>
                                                    setForm({
                                                        ...form,
                                                        currency_code: value,
                                                    })
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {currencies.map(
                                                        (currency) => (
                                                            <SelectItem
                                                                key={
                                                                    currency.id
                                                                }
                                                                value={
                                                                    currency.code
                                                                }
                                                            >
                                                                {currency.code}{' '}
                                                                -{' '}
                                                                {currency.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="issue_date">
                                                Fecha Emisión *
                                            </Label>
                                            <Input
                                                id="issue_date"
                                                type="date"
                                                value={form.issue_date}
                                                onChange={(e) =>
                                                    setForm({
                                                        ...form,
                                                        issue_date:
                                                            e.target.value,
                                                    })
                                                }
                                                required
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="due_date">
                                                Fecha Vencimiento
                                            </Label>
                                            <Input
                                                id="due_date"
                                                type="date"
                                                value={form.due_date}
                                                onChange={(e) =>
                                                    setForm({
                                                        ...form,
                                                        due_date:
                                                            e.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="external_ref">
                                                Referencia Externa
                                            </Label>
                                            <Input
                                                id="external_ref"
                                                placeholder="Ej: PO-12345"
                                                value={form.external_ref}
                                                onChange={(e) =>
                                                    setForm({
                                                        ...form,
                                                        external_ref:
                                                            e.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="notes">Notas</Label>
                                        <Textarea
                                            id="notes"
                                            placeholder="Notas internas..."
                                            value={form.notes}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    notes: e.target.value,
                                                })
                                            }
                                            rows={3}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle>Líneas de Cargo</CardTitle>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={addLine}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Agregar Línea
                                    </Button>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {lines.map((line, index) => (
                                        <div
                                            key={index}
                                            className="grid gap-3 rounded-lg border bg-muted/30 p-4 md:grid-cols-6"
                                        >
                                            <div className="space-y-1">
                                                <Label className="text-xs">
                                                    Código *
                                                </Label>
                                                <Input
                                                    placeholder="FRT"
                                                    value={line.code}
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'code',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="space-y-1 md:col-span-2">
                                                <Label className="text-xs">
                                                    Descripción *
                                                </Label>
                                                <Input
                                                    placeholder="Flete Marítimo"
                                                    value={line.description}
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'description',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="space-y-1">
                                                <Label className="text-xs">
                                                    Cantidad *
                                                </Label>
                                                <Input
                                                    type="number"
                                                    step="0.0001"
                                                    min="0.0001"
                                                    value={line.qty}
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'qty',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="space-y-1">
                                                <Label className="text-xs">
                                                    Precio Unit. *
                                                </Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={line.unit_price}
                                                    onChange={(e) =>
                                                        updateLine(
                                                            index,
                                                            'unit_price',
                                                            e.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                            <div className="flex items-end gap-2">
                                                <div className="flex-1 space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <Label className="text-xs">
                                                            ITBIS
                                                        </Label>
                                                        <Checkbox
                                                            checked={
                                                                line.is_taxable
                                                            }
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                updateLine(
                                                                    index,
                                                                    'is_taxable',
                                                                    !!checked,
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={line.tax_amount}
                                                        readOnly
                                                        className="bg-muted text-muted-foreground"
                                                    />
                                                </div>
                                                {lines.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        className="text-red-400 hover:text-red-500"
                                                        onClick={() =>
                                                            removeLine(index)
                                                        }
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Sidebar - Totals */}
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Resumen</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex justify-between text-sm">
                                        <span>Subtotal</span>
                                        <span className="font-mono">
                                            {currencySymbol}{' '}
                                            {totals.subtotal.toLocaleString(
                                                'en-US',
                                                {
                                                    minimumFractionDigits: 2,
                                                },
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span>Impuestos</span>
                                        <span className="font-mono">
                                            {currencySymbol}{' '}
                                            {totals.tax.toLocaleString(
                                                'en-US',
                                                {
                                                    minimumFractionDigits: 2,
                                                },
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex justify-between border-t pt-2 text-lg font-bold">
                                        <span>Total</span>
                                        <span className="font-mono">
                                            {currencySymbol}{' '}
                                            {totals.total.toLocaleString(
                                                'en-US',
                                                {
                                                    minimumFractionDigits: 2,
                                                },
                                            )}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={
                                    isSubmitting ||
                                    !form.customer_id ||
                                    lines.some((l) => !l.code || !l.description)
                                }
                            >
                                <Save className="mr-2 h-4 w-4" />
                                {isSubmitting
                                    ? 'Guardando...'
                                    : 'Guardar Pre-Factura'}
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                className="w-full"
                                asChild
                            >
                                <Link href={preInvoiceRoutes.index().url}>
                                    Cancelar
                                </Link>
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
