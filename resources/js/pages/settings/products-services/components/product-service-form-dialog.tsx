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
import { type ProductService } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface ProductServiceFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    productService?: ProductService | null;
    currencies: Currency[];
}

export default function ProductServiceFormDialog({
    open,
    onOpenChange,
    productService,
    currencies,
}: ProductServiceFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        code: productService?.code || '',
        name: productService?.name || '',
        description: productService?.description || '',
        type: productService?.type || 'service',
        uom: productService?.uom || '',
        default_currency_id:
            productService?.default_currency_id?.toString() || '',
        default_unit_price:
            productService?.default_unit_price?.toString() || '',
        taxable: productService?.taxable ?? true,
        gl_account_code: productService?.gl_account_code || '',
        is_active: productService?.is_active ?? true,
    });

    useEffect(() => {
        if (open && productService) {
            setData({
                code: productService.code,
                name: productService.name,
                description: productService.description || '',
                type: productService.type,
                uom: productService.uom || '',
                default_currency_id:
                    productService.default_currency_id?.toString() || '',
                default_unit_price:
                    productService.default_unit_price?.toString() || '',
                taxable: productService.taxable,
                gl_account_code: productService.gl_account_code || '',
                is_active: productService.is_active,
            });
        } else if (!open) {
            reset();
        }
    }, [open, productService, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const payload = {
            ...data,
            default_currency_id: data.default_currency_id || null,
            default_unit_price: data.default_unit_price || null,
        };

        if (productService) {
            router.put(
                `/settings/products-services/${productService.id}`,
                payload,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        onOpenChange(false);
                        reset();
                    },
                },
            );
        } else {
            router.post('/settings/products-services', payload, {
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
            <DialogContent className="sm:max-w-[600px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {productService
                                ? 'Editar Producto/Servicio'
                                : 'Nuevo Producto/Servicio'}
                        </DialogTitle>
                        <DialogDescription>
                            Define items para cotizaciones y facturación.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Row 1: Code, Name */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="code" className="text-sm">
                                    Código{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="code"
                                    placeholder="FRT-AIR"
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
                                    required
                                />
                                {errors.code && (
                                    <p className="text-xs text-destructive">
                                        {errors.code}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="name" className="text-sm">
                                    Nombre{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    placeholder="Flete Aéreo"
                                    maxLength={150}
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

                        {/* Row 2: Type, UOM */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="type" className="text-sm">
                                    Tipo{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) =>
                                        setData(
                                            'type',
                                            value as
                                                | 'service'
                                                | 'product'
                                                | 'fee',
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="type"
                                        className={
                                            errors.type
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="service">
                                            Servicio
                                        </SelectItem>
                                        <SelectItem value="product">
                                            Producto
                                        </SelectItem>
                                        <SelectItem value="fee">
                                            Cargo
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.type && (
                                    <p className="text-xs text-destructive">
                                        {errors.type}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="uom" className="text-sm">
                                    Unidad de Medida
                                </Label>
                                <Input
                                    id="uom"
                                    placeholder="kg, cbm, shipment"
                                    maxLength={30}
                                    value={data.uom}
                                    onChange={(e) =>
                                        setData('uom', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        {/* Row 3: Currency, Price */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="default_currency_id"
                                    className="text-sm"
                                >
                                    Moneda por Defecto
                                </Label>
                                <Select
                                    value={data.default_currency_id}
                                    onValueChange={(value) =>
                                        setData('default_currency_id', value)
                                    }
                                >
                                    <SelectTrigger id="default_currency_id">
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {currencies.map((currency) => (
                                            <SelectItem
                                                key={currency.id}
                                                value={currency.id.toString()}
                                            >
                                                {currency.code} -{' '}
                                                {currency.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="default_unit_price"
                                    className="text-sm"
                                >
                                    Precio Unitario
                                </Label>
                                <Input
                                    id="default_unit_price"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.default_unit_price}
                                    onChange={(e) =>
                                        setData(
                                            'default_unit_price',
                                            e.target.value,
                                        )
                                    }
                                    className={
                                        errors.default_unit_price
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </div>
                        </div>

                        {/* Row 4: Description */}
                        <div className="grid gap-1.5">
                            <Label htmlFor="description" className="text-sm">
                                Descripción
                            </Label>
                            <Textarea
                                id="description"
                                placeholder="Descripción del producto o servicio..."
                                maxLength={2000}
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                rows={2}
                            />
                        </div>

                        {/* Row 5: GL Account */}
                        <div className="grid gap-1.5">
                            <Label
                                htmlFor="gl_account_code"
                                className="text-sm"
                            >
                                Cuenta Contable (GL)
                            </Label>
                            <Input
                                id="gl_account_code"
                                placeholder="4100-001"
                                maxLength={50}
                                value={data.gl_account_code}
                                onChange={(e) =>
                                    setData('gl_account_code', e.target.value)
                                }
                            />
                            <p className="text-xs text-muted-foreground">
                                Código de cuenta para integración contable
                                futura
                            </p>
                        </div>

                        {/* Row 6: Checkboxes */}
                        <div className="flex flex-wrap gap-6 pt-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="taxable"
                                    checked={data.taxable}
                                    onCheckedChange={(checked) =>
                                        setData('taxable', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="taxable"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    Gravable (aplica impuestos)
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
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
                                    Item activo
                                </Label>
                            </div>
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
                            {productService ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
