/* eslint-disable react-hooks/exhaustive-deps */
/* eslint-disable @typescript-eslint/no-explicit-any */
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DecimalInput } from '@/components/ui/decimal-input';
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
import { formatCurrency, formatDate } from '@/lib/utils';
import {
    Customer,
    InvoiceAllocationData,
    PaymentMethod,
    PendingInvoice,
    WithholdingData,
} from '@/pages/payments/utils/types/payment';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { AlertCircle, ArrowLeft, Plus, Save, Trash2, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Payment {
    id: number;
    payment_number: string;
    type: 'inbound' | 'outbound';
    customer_id: number;
    payment_method_id: number;
    payment_date: string;
    amount: number;
    currency_code: string;
    exchange_rate: number;
    isr_withholding_amount?: number;
    itbis_withholding_amount?: number;
    allocations: Array<{
        invoice_id: number;
        amount_applied: number;
        invoice: {
            id: number;
            number: string;
            issue_date?: string;
            total_amount?: number;
            balance: number;
            currency_code: string;
        };
    }>;
}

interface Props {
    type?: 'inbound' | 'outbound';
    customers: Customer[];
    paymentMethods: PaymentMethod[];
    pendingInvoices: PendingInvoice[];
    selectedCustomerId?: number | null;
    payment?: Payment;
}

interface PaymentFormData {
    type: 'inbound' | 'outbound';
    customer_id: number | null;
    payment_method_id: number | null;
    payment_date: string;
    amount: number;
    currency_code: string;
    exchange_rate: number;
    isr_withholding_amount: number;
    itbis_withholding_amount: number;
    reference: string;
    notes: string;
    allocations: Array<{
        invoice_id: number;
        amount_applied: number;
    }>;
}

export default function CreatePayment({
    type,
    customers,
    paymentMethods,
    pendingInvoices: initialPendingInvoices,
    selectedCustomerId,
    payment,
}: Props) {
    const isEditing = !!payment;
    const { data, setData, post, put, processing, errors } =
        useForm<PaymentFormData>({
            type: payment?.type || type || 'inbound',
            customer_id: payment?.customer_id || selectedCustomerId || null,
            payment_method_id: payment?.payment_method_id || null,
            payment_date:
                payment?.payment_date || new Date().toISOString().split('T')[0],
            amount: payment?.amount || 0,
            currency_code: payment?.currency_code || 'DOP',
            exchange_rate: payment?.exchange_rate || 1,
            isr_withholding_amount: 0,
            itbis_withholding_amount: 0,
            reference: payment?.reference || '',
            notes: payment?.notes || '',
            allocations:
                payment?.allocations?.map((alloc) => ({
                    invoice_id: alloc.invoice_id,
                    amount_applied: alloc.amount_applied,
                })) || [],
        });

    const [pendingInvoices, setPendingInvoices] = useState<PendingInvoice[]>(
        initialPendingInvoices.map((inv) => ({
            ...inv,
            balance: parseFloat(String(inv.balance)),
            total_amount: parseFloat(String(inv.total_amount)),
            amount_paid: parseFloat(String(inv.amount_paid)),
        })),
    );
    const [loadingInvoices, setLoadingInvoices] = useState(false);
    const [selectedInvoices, setSelectedInvoices] = useState<
        Map<number, InvoiceAllocationData>
    >(() => {
        const map = new Map<number, InvoiceAllocationData>();
        if (payment?.allocations) {
            payment.allocations.forEach((alloc) => {
                const amountApplied =
                    parseFloat(String(alloc.amount_applied)) || 0;
                // El balance puede venir del backend o podemos usar amount_applied como referencia
                const currentBalance = alloc.invoice?.balance
                    ? parseFloat(String(alloc.invoice.balance))
                    : 0;
                // En modo edición, el balance ya tiene restado este pago, así que lo sumamos de vuelta
                const adjustedBalance = currentBalance + amountApplied;

                map.set(alloc.invoice_id, {
                    invoice_id: alloc.invoice_id,
                    invoice_number: alloc.invoice?.number || 'N/A',
                    balance: adjustedBalance,
                    amount_applied: amountApplied,
                    currency_code:
                        alloc.invoice?.currency_code || payment.currency_code,
                });
            });
        }
        return map;
    });
    const [withholdings, setWithholdings] = useState<WithholdingData[]>(() => {
        const initialWithholdings: WithholdingData[] = [];
        if (
            payment?.isr_withholding_amount &&
            payment.isr_withholding_amount > 0
        ) {
            initialWithholdings.push({
                type: 'isr',
                percentage: 0, // We don't track percentage in DB, so 0 or calc
                amount: parseFloat(String(payment.isr_withholding_amount)),
                description: 'Retención ISR (Cargada)',
            });
        }
        if (
            payment?.itbis_withholding_amount &&
            payment.itbis_withholding_amount > 0
        ) {
            initialWithholdings.push({
                type: 'itbis',
                percentage: 0,
                amount: parseFloat(String(payment.itbis_withholding_amount)),
                description: 'Retención ITBIS (Cargada)',
            });
        }
        return initialWithholdings;
    });

    // Fetch pending invoices when customer changes
    // Fetch pending invoices when customer changes
    const fetchPendingInvoices = async (customerId: number) => {
        setLoadingInvoices(true);
        try {
            const response = await axios.get(
                `/customers/${customerId}/pending-invoices`,
            );
            // Ensure strict numeric types for calculations
            const sanitizedInvoices = response.data.map(
                (inv: PendingInvoice) => ({
                    ...inv,
                    balance: parseFloat(String(inv.balance)),
                    total_amount: parseFloat(String(inv.total_amount)),
                    amount_paid: parseFloat(String(inv.amount_paid)),
                }),
            );
            setPendingInvoices(sanitizedInvoices);
        } catch (error) {
            console.error('Error loading invoices:', error);
        } finally {
            setLoadingInvoices(false);
        }
    };

    useEffect(() => {
        // En modo edición, no limpiamos las facturas seleccionadas
        if (isEditing) {
            return;
        }

        if (data.customer_id) {
            fetchPendingInvoices(data.customer_id);
        } else {
            setPendingInvoices([]);
            setSelectedInvoices(new Map());
        }
    }, [data.customer_id, isEditing]);

    // Sync withholdings state with form data
    useEffect(() => {
        const isrTotal = withholdings
            .filter((wh) => wh.type === 'isr')
            .reduce((sum, wh) => sum + wh.amount, 0);
        const itbisTotal = withholdings
            .filter((wh) => wh.type === 'itbis')
            .reduce((sum, wh) => sum + wh.amount, 0);

        setData((prev) => ({
            ...prev,
            isr_withholding_amount: isrTotal,
            itbis_withholding_amount: itbisTotal,
        }));
    }, [withholdings]);

    // Handle invoice selection
    const handleInvoiceToggle = (invoice: PendingInvoice, checked: boolean) => {
        const newSelected = new Map(selectedInvoices);

        if (checked) {
            newSelected.set(invoice.id, {
                invoice_id: invoice.id,
                invoice_number: invoice.number,
                balance: parseFloat(String(invoice.balance)),
                amount_applied: parseFloat(String(invoice.balance)),
                currency_code: invoice.currency_code,
            });
        } else {
            newSelected.delete(invoice.id);
        }

        setSelectedInvoices(newSelected);
        updateAllocations(newSelected);
    };

    // Handle amount applied change
    const handleAmountAppliedChange = (
        invoice: PendingInvoice,
        value: number,
    ) => {
        const numValue = value || 0;
        const newSelected = new Map(selectedInvoices);

        if (numValue > 0) {
            newSelected.set(invoice.id, {
                invoice_id: invoice.id,
                invoice_number: invoice.number,
                balance: invoice.balance,
                amount_applied: numValue,
                currency_code: invoice.currency_code,
            });
        } else {
            newSelected.delete(invoice.id);
        }

        setSelectedInvoices(newSelected);
        updateAllocations(newSelected);
    };

    // Update form allocations
    const updateAllocations = (
        selected: Map<number, InvoiceAllocationData>,
    ) => {
        const allocations = Array.from(selected.values())
            .filter((alloc) => alloc.amount_applied > 0)
            .map((alloc) => ({
                invoice_id: alloc.invoice_id,
                amount_applied: alloc.amount_applied,
            }));
        setData('allocations', allocations);
    };

    // Calculate totals
    const totalApplied = Array.from(selectedInvoices.values()).reduce(
        (sum, alloc) => sum + (parseFloat(String(alloc.amount_applied)) || 0),
        0,
    );

    const totalWithholdings = withholdings.reduce(
        (sum, wh) => sum + wh.amount,
        0,
    );

    const totalReceived = data.amount;
    // Monto a pagar = Monto Factura - Retenciones
    // Entonces: Total Recibido + Retenciones = Total Aplicado
    const difference = totalReceived + totalWithholdings - totalApplied;

    // Validation
    const canSubmit = () => {
        if (!data.customer_id || !data.payment_method_id) return false;
        if (data.amount <= 0) return false;
        // Check that no allocation exceeds balance
        for (const alloc of selectedInvoices.values()) {
            if (alloc.amount_applied > alloc.balance) return false;
        }

        // Check that totals match (allow unapplied amount, but not over-application)
        // difference = received + withholdings - applied
        // If difference > 0: Valid (Unapplied Balance)
        // If difference < 0: Invalid (Over Applied)
        return difference >= -0.01;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(`/payments/${payment.id}`, {
                onSuccess: () => {
                    // Redirect handled by backend
                },
                onError: (errors) => {
                    console.error('Payment update errors:', errors);
                },
            });
        } else {
            post('/payments', {
                onSuccess: () => {
                    // Redirect handled by backend
                },
                onError: (errors) => {
                    console.error('Payment creation errors:', errors);
                },
            });
        }
    };

    // Add withholding
    const addWithholding = () => {
        const initialPercentage = 18;
        const initialAmount = parseFloat(
            ((initialPercentage / 100) * totalApplied).toFixed(2),
        );

        setWithholdings([
            ...withholdings,
            {
                type: 'itbis',
                percentage: initialPercentage,
                amount: initialAmount,
                description: '',
            },
        ]);
    };

    const removeWithholding = (index: number) => {
        setWithholdings(withholdings.filter((_, i) => i !== index));
    };

    const updateWithholding = (
        index: number,
        field: keyof WithholdingData,
        value: any,
    ) => {
        const updated = [...withholdings];
        updated[index] = { ...updated[index], [field]: value };

        // Auto-calculate amount when percentage changes
        if (field === 'percentage') {
            const calculatedAmount = (value / 100) * totalApplied;
            updated[index].amount = parseFloat(calculatedAmount.toFixed(2));
        }

        setWithholdings(updated);
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pagos', href: '/payments' },
                isEditing
                    ? {
                          title: payment.payment_number,
                          href: `/payments/${payment.id}`,
                      }
                    : { title: 'Registrar Cobro', href: '/payments/create' },
            ]}
        >
            <Head
                title={
                    isEditing
                        ? `Editar ${payment.payment_number}`
                        : 'Registrar Cobro'
                }
            />

            <div className="space-y-6 px-6 py-6">
                <div className="flex items-center justify-between">
                    <div>
                        <div className="mb-4 flex justify-between gap-4">
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={() =>
                                    isEditing
                                        ? router.visit(
                                              `/payments/${payment?.id}`,
                                          )
                                        : router.visit('/payments')
                                }
                            >
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                            <div>
                                <h1 className="text-3xl font-bold tracking-tight">
                                    {isEditing
                                        ? `Editar ${payment.payment_number}`
                                        : 'Registrar Cobro'}
                                </h1>
                                <p className="text-muted-foreground">
                                    {isEditing
                                        ? 'Modifica la información del pago'
                                        : 'Registra un cobro y aplícalo a las facturas pendientes'}
                                </p>
                            </div>
                        </div>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.visit('/payments')}
                    >
                        <X className="mr-2 h-4 w-4" />
                        Cancelar
                    </Button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Payment Header */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Pago</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-6 md:grid-cols-2">
                            {/* Customer */}
                            <div className="space-y-2">
                                <Label htmlFor="customer_id">
                                    Cliente{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={data.customer_id?.toString() || ''}
                                    onValueChange={(value) =>
                                        setData('customer_id', parseInt(value))
                                    }
                                >
                                    <SelectTrigger
                                        id="customer_id"
                                        className={
                                            errors.customer_id
                                                ? 'border-red-500'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Selecciona un cliente" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {customers.map((customer) => (
                                            <SelectItem
                                                key={customer.id}
                                                value={customer.id.toString()}
                                            >
                                                {customer.fiscal_name ||
                                                    customer.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.customer_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.customer_id}
                                    </p>
                                )}
                            </div>

                            {/* Payment Date */}
                            <div className="space-y-2">
                                <Label htmlFor="payment_date">
                                    Fecha{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="payment_date"
                                    type="date"
                                    value={data.payment_date}
                                    onChange={(e) =>
                                        setData('payment_date', e.target.value)
                                    }
                                    className={
                                        errors.payment_date
                                            ? 'border-red-500'
                                            : ''
                                    }
                                />
                                {errors.payment_date && (
                                    <p className="text-sm text-red-500">
                                        {errors.payment_date}
                                    </p>
                                )}
                            </div>

                            {/* Payment Method */}
                            <div className="space-y-2">
                                <Label htmlFor="payment_method_id">
                                    Método de Pago{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={
                                        data.payment_method_id?.toString() || ''
                                    }
                                    onValueChange={(value) =>
                                        setData(
                                            'payment_method_id',
                                            parseInt(value),
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="payment_method_id"
                                        className={
                                            errors.payment_method_id
                                                ? 'border-red-500'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Selecciona método" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {paymentMethods.map((method) => (
                                            <SelectItem
                                                key={method.id}
                                                value={method.id.toString()}
                                            >
                                                {method.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.payment_method_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.payment_method_id}
                                    </p>
                                )}
                            </div>

                            {/* Reference */}
                            <div className="space-y-2">
                                <Label htmlFor="reference">Referencia</Label>
                                <Input
                                    id="reference"
                                    type="text"
                                    placeholder="Ej: Cheque #1234"
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData('reference', e.target.value)
                                    }
                                />
                            </div>

                            {/* Currency */}
                            <div className="space-y-2">
                                <Label htmlFor="currency_code">
                                    Moneda{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={data.currency_code}
                                    onValueChange={(value) =>
                                        setData('currency_code', value)
                                    }
                                >
                                    <SelectTrigger id="currency_code">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="DOP">DOP</SelectItem>
                                        <SelectItem value="USD">USD</SelectItem>
                                        <SelectItem value="EUR">EUR</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Exchange Rate */}
                            <div className="space-y-2">
                                <Label htmlFor="exchange_rate">
                                    Tasa de Cambio{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <DecimalInput
                                    id="exchange_rate"
                                    value={data.exchange_rate}
                                    onChange={(value) =>
                                        setData('exchange_rate', value || 1)
                                    }
                                />
                                {data.currency_code !== 'DOP' &&
                                    data.exchange_rate === 1 && (
                                        <div className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-2 text-xs text-amber-900">
                                            <AlertCircle className="mt-0.5 h-3 w-3 flex-shrink-0" />
                                            <p>
                                                Estás usando tasa 1.00 para una
                                                moneda extranjera.
                                            </p>
                                        </div>
                                    )}
                            </div>

                            {/* Total Received */}
                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="amount">
                                        Total Recibido{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    {selectedInvoices.size > 0 && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="h-6 text-xs"
                                            onClick={() => {
                                                const amountToPay =
                                                    totalApplied -
                                                    totalWithholdings;
                                                setData('amount', amountToPay);
                                            }}
                                        >
                                            Copiar "Monto a Pagar"
                                        </Button>
                                    )}
                                </div>
                                <DecimalInput
                                    id="amount"
                                    value={data.amount}
                                    onChange={(value) =>
                                        setData('amount', value || 0)
                                    }
                                    className={
                                        errors.amount ? 'border-red-500' : ''
                                    }
                                />
                                {errors.amount && (
                                    <p className="text-sm text-red-500">
                                        {errors.amount}
                                    </p>
                                )}
                            </div>

                            {/* Notes */}
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="notes">Notas</Label>
                                <Textarea
                                    id="notes"
                                    rows={3}
                                    placeholder="Notas adicionales sobre el pago..."
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                />
                            </div>
                        </CardContent>

                        {/* Totals Summary */}
                        {selectedInvoices.size > 0 && (
                            <div className="flex justify-end px-6">
                                <div className="w-96 space-y-2 rounded-lg border bg-muted/50 p-4">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">
                                            Total Pendiente:
                                        </span>
                                        <span className="font-mono font-semibold">
                                            {formatCurrency(
                                                totalApplied,
                                                data.currency_code,
                                            )}
                                        </span>
                                    </div>
                                    {totalWithholdings > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">
                                                Retenciones:
                                            </span>
                                            <span className="font-mono text-red-600">
                                                -{' '}
                                                {formatCurrency(
                                                    totalWithholdings,
                                                    data.currency_code,
                                                )}
                                            </span>
                                        </div>
                                    )}
                                    <div className="flex justify-between border-t pt-2">
                                        <span className="font-medium text-muted-foreground">
                                            Monto a Pagar:
                                        </span>
                                        <span className="font-mono font-semibold">
                                            {formatCurrency(
                                                totalApplied -
                                                    totalWithholdings,
                                                data.currency_code,
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">
                                            Total Recibido:
                                        </span>
                                        <span className="font-mono">
                                            {formatCurrency(
                                                totalReceived,
                                                data.currency_code,
                                            )}
                                        </span>
                                    </div>
                                    <div className="border-t pt-3">
                                        <div className="flex justify-between">
                                            <span className="font-semibold">
                                                {difference >= 0
                                                    ? 'Monto Sin Aplicar:'
                                                    : 'Faltante:'}
                                            </span>
                                            <span
                                                className={`font-mono font-bold ${
                                                    difference >= 0
                                                        ? 'text-blue-600'
                                                        : 'text-red-600'
                                                }`}
                                            >
                                                {formatCurrency(
                                                    Math.abs(difference),
                                                    data.currency_code,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Validation Messages */}
                        {selectedInvoices.size > 0 && (
                            <div className="space-y-2 px-6">
                                {difference > 0.01 && (
                                    <div className="flex items-start gap-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                                        <AlertCircle className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                        <p>
                                            Tienes{' '}
                                            <strong>
                                                {formatCurrency(
                                                    difference,
                                                    data.currency_code,
                                                )}
                                            </strong>{' '}
                                            sin aplicar. Este monto quedará como
                                            saldo a favor (Pending).
                                        </p>
                                    </div>
                                )}

                                {difference < -0.01 && (
                                    <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                                        <AlertCircle className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                        <p>
                                            El total ha pagar es de{' '}
                                            <strong>
                                                {formatCurrency(
                                                    Math.abs(difference),
                                                    data.currency_code,
                                                )}
                                            </strong>{' '}
                                            . Verifica los montos.
                                        </p>
                                    </div>
                                )}

                                {Array.from(selectedInvoices.values()).some(
                                    (alloc) =>
                                        alloc.amount_applied > alloc.balance,
                                ) && (
                                    <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-900">
                                        <AlertCircle className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                        <p>
                                            Una o más facturas tienen un monto a
                                            abonar que excede el saldo
                                            pendiente.
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </Card>

                    {/* Pending Invoices Grid (Create Mode Only) */}
                    {!isEditing && data.customer_id && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Facturas Pendientes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {loadingInvoices ? (
                                    <div className="flex items-center justify-center py-8">
                                        <p className="text-muted-foreground">
                                            Cargando facturas...
                                        </p>
                                    </div>
                                ) : pendingInvoices.length === 0 ? (
                                    <div className="flex items-center justify-center py-8">
                                        <p className="text-muted-foreground">
                                            No hay facturas pendientes para este
                                            cliente
                                        </p>
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b">
                                                    <th className="p-2 text-left">
                                                        <div className="w-4" />
                                                    </th>
                                                    <th className="p-2 text-left">
                                                        Folio
                                                    </th>
                                                    <th className="p-2 text-left">
                                                        Fecha
                                                    </th>
                                                    <th className="p-2 text-center">
                                                        Moneda
                                                    </th>
                                                    <th className="p-2 text-right">
                                                        Total
                                                    </th>
                                                    <th className="p-2 text-right">
                                                        Saldo Pendiente
                                                    </th>
                                                    <th className="p-2 text-right">
                                                        Monto a Abonar
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {pendingInvoices.map(
                                                    (invoice) => {
                                                        const isSelected =
                                                            selectedInvoices.has(
                                                                invoice.id,
                                                            );
                                                        const allocation =
                                                            selectedInvoices.get(
                                                                invoice.id,
                                                            );
                                                        const isDifferentCurrency =
                                                            invoice.currency_code !==
                                                            data.currency_code;

                                                        return (
                                                            <tr
                                                                key={invoice.id}
                                                                className={`border-b hover:bg-muted/50 ${isDifferentCurrency ? 'bg-amber-50/50 dark:bg-amber-950/20' : ''}`}
                                                            >
                                                                <td className="p-2">
                                                                    <Checkbox
                                                                        checked={
                                                                            isSelected
                                                                        }
                                                                        onCheckedChange={(
                                                                            checked,
                                                                        ) =>
                                                                            handleInvoiceToggle(
                                                                                invoice,
                                                                                checked as boolean,
                                                                            )
                                                                        }
                                                                    />
                                                                </td>
                                                                <td className="p-2 font-mono text-sm">
                                                                    {
                                                                        invoice.number
                                                                    }
                                                                </td>
                                                                <td className="p-2 text-sm">
                                                                    {formatDate(
                                                                        invoice.issue_date,
                                                                    )}
                                                                </td>
                                                                <td
                                                                    className={`p-2 text-center font-mono text-xs ${isDifferentCurrency ? 'font-semibold text-amber-600' : ''}`}
                                                                >
                                                                    {
                                                                        invoice.currency_code
                                                                    }
                                                                    {isDifferentCurrency && (
                                                                        <span
                                                                            className="ml-1 text-amber-500"
                                                                            title="Moneda diferente al pago"
                                                                        >
                                                                            ⚠
                                                                        </span>
                                                                    )}
                                                                </td>
                                                                <td className="p-2 text-right font-mono">
                                                                    {formatCurrency(
                                                                        invoice.total_amount,
                                                                        invoice.currency_code,
                                                                    )}
                                                                </td>
                                                                <td className="p-2 text-right font-mono font-semibold">
                                                                    {formatCurrency(
                                                                        parseFloat(
                                                                            String(
                                                                                invoice.balance,
                                                                            ),
                                                                        ) || 0,
                                                                        invoice.currency_code,
                                                                    )}
                                                                </td>
                                                                <td className="p-2">
                                                                    <DecimalInput
                                                                        min={0}
                                                                        max={
                                                                            invoice.balance
                                                                        }
                                                                        value={
                                                                            allocation?.amount_applied ||
                                                                            0
                                                                        }
                                                                        onChange={(
                                                                            value,
                                                                        ) =>
                                                                            handleAmountAppliedChange(
                                                                                invoice,
                                                                                value,
                                                                            )
                                                                        }
                                                                        className={`text-right font-mono ${
                                                                            allocation &&
                                                                            allocation.amount_applied >
                                                                                invoice.balance
                                                                                ? 'border-red-500'
                                                                                : ''
                                                                        }`}
                                                                    />
                                                                </td>
                                                            </tr>
                                                        );
                                                    },
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Applied Invoices Grid (Edit Mode Only) */}
                    {isEditing && selectedInvoices.size > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Facturas Aplicadas</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="p-2 text-left">
                                                    Folio
                                                </th>
                                                <th className="p-2 text-left">
                                                    Fecha
                                                </th>
                                                <th className="p-2 text-center">
                                                    Moneda
                                                </th>
                                                <th className="p-2 text-right">
                                                    Saldo Pendiente
                                                </th>
                                                <th className="p-2 text-right">
                                                    Total Factura
                                                </th>
                                                <th className="p-2 text-right">
                                                    Monto Pagado
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {payment?.allocations?.map(
                                                (alloc) => {
                                                    const balance = parseFloat(
                                                        String(
                                                            alloc.invoice
                                                                ?.balance || 0,
                                                        ),
                                                    );
                                                    const amountApplied =
                                                        parseFloat(
                                                            String(
                                                                alloc.amount_applied,
                                                            ),
                                                        ) || 0;
                                                    return (
                                                        <tr
                                                            key={
                                                                alloc.invoice_id
                                                            }
                                                            className="border-b hover:bg-muted/50"
                                                        >
                                                            <td className="p-2 font-mono text-sm">
                                                                {alloc.invoice
                                                                    ?.number ||
                                                                    'N/A'}
                                                            </td>
                                                            <td className="p-2 text-sm">
                                                                {alloc.invoice
                                                                    ?.issue_date
                                                                    ? formatDate(
                                                                          alloc
                                                                              .invoice
                                                                              .issue_date,
                                                                      )
                                                                    : '-'}
                                                            </td>
                                                            <td className="p-2 text-center font-mono text-xs">
                                                                {alloc.invoice
                                                                    ?.currency_code ||
                                                                    payment.currency_code}
                                                            </td>
                                                            <td className="p-2 text-right font-mono font-semibold">
                                                                {formatCurrency(
                                                                    balance,
                                                                    alloc
                                                                        .invoice
                                                                        ?.currency_code ||
                                                                        payment.currency_code,
                                                                )}
                                                            </td>
                                                            <td className="p-2 text-right font-mono">
                                                                {formatCurrency(
                                                                    parseFloat(
                                                                        String(
                                                                            alloc
                                                                                .invoice
                                                                                ?.total_amount ||
                                                                                0,
                                                                        ),
                                                                    ),
                                                                    alloc
                                                                        .invoice
                                                                        ?.currency_code ||
                                                                        payment.currency_code,
                                                                )}
                                                            </td>

                                                            <td className="p-2 text-right font-mono font-semibold text-green-600">
                                                                {formatCurrency(
                                                                    amountApplied,
                                                                    alloc
                                                                        .invoice
                                                                        ?.currency_code ||
                                                                        payment.currency_code,
                                                                )}
                                                            </td>
                                                        </tr>
                                                    );
                                                },
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Withholdings */}
                    {selectedInvoices.size > 0 && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Retenciones (DGII)</CardTitle>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={addWithholding}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar Retención
                                </Button>
                            </CardHeader>
                            <CardContent>
                                {withholdings.length === 0 ? (
                                    <p className="text-center text-sm text-muted-foreground">
                                        No hay retenciones agregadas
                                    </p>
                                ) : (
                                    <div className="space-y-4">
                                        {withholdings.map((wh, index) => (
                                            <div
                                                key={index}
                                                className="grid gap-4 rounded-lg border p-4 md:grid-cols-5"
                                            >
                                                <div className="space-y-2">
                                                    <Label>Tipo</Label>
                                                    <Select
                                                        value={wh.type}
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            updateWithholding(
                                                                index,
                                                                'type',
                                                                value,
                                                            )
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="itbis">
                                                                ITBIS
                                                            </SelectItem>
                                                            <SelectItem value="isr">
                                                                ISR
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Porcentaje %</Label>
                                                    <DecimalInput
                                                        value={wh.percentage}
                                                        onChange={(value) =>
                                                            updateWithholding(
                                                                index,
                                                                'percentage',
                                                                value || 0,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label>Monto</Label>
                                                    <DecimalInput
                                                        value={wh.amount}
                                                        onChange={(value) =>
                                                            updateWithholding(
                                                                index,
                                                                'amount',
                                                                value || 0,
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="space-y-2 md:col-span-2">
                                                    <Label>Descripción</Label>
                                                    <div className="flex gap-2">
                                                        <Input
                                                            type="text"
                                                            value={
                                                                wh.description
                                                            }
                                                            onChange={(e) =>
                                                                updateWithholding(
                                                                    index,
                                                                    'description',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            placeholder="Descripción de la retención"
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            size="icon"
                                                            onClick={() =>
                                                                removeWithholding(
                                                                    index,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Submit Button */}
                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/payments')}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={!canSubmit() || processing}
                        >
                            <Save className="mr-2 h-4 w-4" />
                            {processing
                                ? 'Guardando...'
                                : isEditing
                                  ? 'Actualizar Pago'
                                  : 'Guardar Cobro'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
