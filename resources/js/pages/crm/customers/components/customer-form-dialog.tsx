import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { type Customer } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface CustomerFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    customer?: Customer | null;
    currencies: Currency[];
}

export default function CustomerFormDialog({
    open,
    onOpenChange,
    customer,
    currencies,
}: CustomerFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        code: customer?.code || '',
        name: customer?.name || '',
        tax_id: customer?.tax_id || '',
        tax_id_type: customer?.tax_id_type || 'RNC', // Campo Nuevo
        fiscal_name: customer?.fiscal_name || '', // Campo Nuevo
        ncf_type_default: customer?.ncf_type_default || 'B01', // Campo Nuevo
        billing_address: customer?.billing_address || '',
        shipping_address: customer?.shipping_address || '',
        city: customer?.city || '',
        state: customer?.state || '',
        country: customer?.country || '',
        email_billing: customer?.email_billing || '',
        phone: customer?.phone || '',
        website: customer?.website || '',
        status: customer?.status || 'prospect',
        credit_limit: customer?.credit_limit?.toString() || '',
        currency_id: customer?.currency_id?.toString() || '',
        payment_terms: customer?.payment_terms || '',
        notes: customer?.notes || '',
        is_active: customer?.is_active ?? true,
    });

    useEffect(() => {
        if (open && customer) {
            setData({
                code: customer.code || '',
                name: customer.name,
                tax_id: customer.tax_id || '',
                tax_id_type: customer.tax_id_type || 'RNC', // Campo Nuevo
                fiscal_name: customer.fiscal_name || '', // Campo Nuevo
                ncf_type_default: customer.ncf_type_default || 'B01', // Campo Nuevo
                billing_address: customer.billing_address || '',
                shipping_address: customer.shipping_address || '',
                city: customer.city || '',
                state: customer.state || '',
                country: customer.country || '',
                email_billing: customer.email_billing || '',
                phone: customer.phone || '',
                website: customer.website || '',
                status: customer.status,
                credit_limit: customer.credit_limit?.toString() || '',
                currency_id: customer.currency_id?.toString() || '',
                payment_terms: customer.payment_terms || '',
                notes: customer.notes || '',
                is_active: customer.is_active,
            });
        } else if (!open) {
            reset();
        }
    }, [open, customer, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const payload = {
            ...data,
            code: data.code || null,
            credit_limit: data.credit_limit
                ? parseFloat(data.credit_limit)
                : null,
            currency_id: data.currency_id ? parseInt(data.currency_id) : null,
        };

        if (customer) {
            router.put(`/crm/customers/${customer.id}`, payload, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            router.post('/crm/customers', payload, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[700px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {customer ? 'Editar Cliente' : 'Nuevo Cliente'}
                        </DialogTitle>
                        <DialogDescription>
                            Gestiona información de clientes para cotizaciones y
                            facturación.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Section: Identification */}
                        <div className="text-sm font-medium text-muted-foreground">
                            Identificación
                        </div>
                        <div className="grid grid-cols-3 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="code" className="text-sm">
                                    Código
                                </Label>
                                <Input
                                    id="code"
                                    placeholder="CUST001"
                                    maxLength={30}
                                    value={data.code}
                                    onChange={(e) =>
                                        setData(
                                            'code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    className={
                                        errors.code ? 'border-destructive' : ''
                                    }
                                />
                                {errors.code && (
                                    <p className="text-xs text-destructive">
                                        {errors.code}
                                    </p>
                                )}
                            </div>
                            <div className="col-span-2 grid gap-1.5">
                                <Label htmlFor="name" className="text-sm">
                                    Nombre{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    placeholder="Nombre del cliente"
                                    maxLength={200}
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    className={
                                        errors.name ? 'border-destructive' : ''
                                    }
                                    required
                                />
                                {errors.name && (
                                    <p className="text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="tax_id_type"
                                    className="text-sm"
                                >
                                    Tipo de Identificación
                                </Label>
                                <Select
                                    value={data.tax_id_type}
                                    onValueChange={(value) =>
                                        setData(
                                            'tax_id_type',
                                            value as 'RNC' | 'CEDULA' | 'OTHER',
                                        )
                                    }
                                >
                                    <SelectTrigger id="tax_id_type">
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="RNC">RNC</SelectItem>
                                        <SelectItem value="CEDULA">
                                            Cédula
                                        </SelectItem>
                                        <SelectItem value="OTHER">
                                            Otro
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.tax_id_type && (
                                    <p className="text-xs text-destructive">
                                        {errors.tax_id_type}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-3">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="tax_id" className="text-sm">
                                        RNC / NIF / VAT
                                    </Label>
                                    <Input
                                        id="tax_id"
                                        placeholder={
                                            data.tax_id_type === 'RNC'
                                                ? '123456789 (9 dígitos)'
                                                : data.tax_id_type === 'CEDULA'
                                                  ? '12345678901 (11 dígitos)'
                                                  : 'Número de identificación'
                                        }
                                        maxLength={20}
                                        value={data.tax_id}
                                        onChange={(e) =>
                                            setData('tax_id', e.target.value)
                                        }
                                        className={
                                            errors.tax_id
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    />
                                    {errors.tax_id && (
                                        <p className="text-xs text-destructive">
                                            {errors.tax_id}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="ncf_type_default"
                                    className="text-sm"
                                >
                                    Tipo de Comprobante
                                </Label>
                                <Select
                                    value={data.ncf_type_default}
                                    onValueChange={(value) =>
                                        setData(
                                            'ncf_type_default',
                                            value as 'B01' | 'B02' | 'B14',
                                        )
                                    }
                                >
                                    <SelectTrigger id="ncf_type_default">
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="B01">
                                            B01 - Crédito Fiscal
                                        </SelectItem>
                                        <SelectItem value="B02">
                                            B02 - Consumidor Final
                                        </SelectItem>
                                        <SelectItem value="B14">
                                            B14 - Gubernamental
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.ncf_type_default && (
                                    <p className="text-xs text-destructive">
                                        {errors.ncf_type_default}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="status" className="text-sm">
                                    Estado{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData(
                                            'status',
                                            value as
                                                | 'prospect'
                                                | 'active'
                                                | 'inactive',
                                        )
                                    }
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="prospect">
                                            Prospecto
                                        </SelectItem>
                                        <SelectItem value="active">
                                            Activo
                                        </SelectItem>
                                        <SelectItem value="inactive">
                                            Inactivo
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="fiscal_name" className="text-sm">
                                Nombre Fiscal
                            </Label>
                            <Input
                                id="fiscal_name"
                                placeholder="Nombre legal para facturación"
                                maxLength={255}
                                value={data.fiscal_name}
                                onChange={(e) =>
                                    setData('fiscal_name', e.target.value)
                                }
                                className={
                                    errors.fiscal_name
                                        ? 'border-destructive'
                                        : ''
                                }
                            />
                            {errors.fiscal_name && (
                                <p className="text-xs text-destructive">
                                    {errors.fiscal_name}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                Si se deja vacío, se usará el nombre del cliente
                            </p>
                        </div>

                        {/* Section: Contact */}
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            Contacto
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="email_billing"
                                    className="text-sm"
                                >
                                    Email Facturación
                                </Label>
                                <Input
                                    id="email_billing"
                                    type="email"
                                    placeholder="facturacion@empresa.com"
                                    maxLength={255}
                                    value={data.email_billing}
                                    onChange={(e) =>
                                        setData('email_billing', e.target.value)
                                    }
                                    className={
                                        errors.email_billing
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                                {errors.email_billing && (
                                    <p className="text-xs text-destructive">
                                        {errors.email_billing}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="phone" className="text-sm">
                                    Teléfono
                                </Label>
                                <Input
                                    id="phone"
                                    placeholder="+1 809-555-0100"
                                    maxLength={50}
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid gap-1.5">
                            <Label htmlFor="website" className="text-sm">
                                Sitio Web
                            </Label>
                            <Input
                                id="website"
                                type="url"
                                placeholder="https://www.empresa.com"
                                maxLength={255}
                                value={data.website}
                                onChange={(e) =>
                                    setData('website', e.target.value)
                                }
                                className={
                                    errors.website ? 'border-destructive' : ''
                                }
                            />
                            {errors.website && (
                                <p className="text-xs text-destructive">
                                    {errors.website}
                                </p>
                            )}
                        </div>

                        {/* Section: Address */}
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            Dirección
                        </div>
                        <div className="grid gap-1.5">
                            <Label
                                htmlFor="billing_address"
                                className="text-sm"
                            >
                                Dirección de Facturación
                            </Label>
                            <Textarea
                                id="billing_address"
                                placeholder="Calle, número, edificio..."
                                maxLength={2000}
                                value={data.billing_address}
                                onChange={(e) =>
                                    setData('billing_address', e.target.value)
                                }
                                rows={2}
                            />
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="city" className="text-sm">
                                    Ciudad
                                </Label>
                                <Input
                                    id="city"
                                    placeholder="Santo Domingo"
                                    maxLength={100}
                                    value={data.city}
                                    onChange={(e) =>
                                        setData('city', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="state" className="text-sm">
                                    Provincia / Estado
                                </Label>
                                <Input
                                    id="state"
                                    placeholder="Distrito Nacional"
                                    maxLength={100}
                                    value={data.state}
                                    onChange={(e) =>
                                        setData('state', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="country" className="text-sm">
                                    País
                                </Label>
                                <Input
                                    id="country"
                                    placeholder="República Dominicana"
                                    maxLength={100}
                                    value={data.country}
                                    onChange={(e) =>
                                        setData('country', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        {/* Section: Financial */}
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            Información Financiera
                        </div>
                        <div className="grid grid-cols-3 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="currency_id"
                                    className="text-sm"
                                >
                                    Moneda
                                </Label>
                                <Select
                                    value={data.currency_id}
                                    onValueChange={(value) =>
                                        setData('currency_id', value)
                                    }
                                >
                                    <SelectTrigger id="currency_id">
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {currencies.map((curr) => (
                                            <SelectItem
                                                key={curr.id}
                                                value={curr.id.toString()}
                                            >
                                                {curr.code} - {curr.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="credit_limit"
                                    className="text-sm"
                                >
                                    Límite de Crédito
                                </Label>
                                <Input
                                    id="credit_limit"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.credit_limit}
                                    onChange={(e) =>
                                        setData('credit_limit', e.target.value)
                                    }
                                    className={
                                        errors.credit_limit
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="payment_terms"
                                    className="text-sm"
                                >
                                    Términos de Pago
                                </Label>
                                <Input
                                    id="payment_terms"
                                    placeholder="30 días"
                                    maxLength={100}
                                    value={data.payment_terms}
                                    onChange={(e) =>
                                        setData('payment_terms', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        {/* Section: Notes */}
                        <div className="mt-2 text-sm font-medium text-muted-foreground">
                            Notas
                        </div>
                        <div className="grid gap-1.5">
                            <Textarea
                                id="notes"
                                placeholder="Notas internas sobre el cliente..."
                                maxLength={5000}
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                rows={2}
                            />
                        </div>

                        {/* Active */}
                        <div className="flex items-center space-x-2 pt-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked === true)
                                }
                            />
                            <Label
                                htmlFor="is_active"
                                className="cursor-pointer text-sm font-normal"
                            >
                                Cliente activo en el sistema
                            </Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            {customer ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
