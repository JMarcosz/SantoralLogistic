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
import currencyRoutes from '@/routes/currencies';
import { type Currency } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface CurrencyFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    currency?: Currency | null;
}

export default function CurrencyFormDialog({
    open,
    onOpenChange,
    currency,
}: CurrencyFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        code: currency?.code || '',
        name: currency?.name || '',
        symbol: currency?.symbol || '',
        is_default: currency?.is_default || false,
    });

    // Reset form when dialog opens/closes or currency changes
    useEffect(() => {
        if (open && currency) {
            setData({
                code: currency.code,
                name: currency.name,
                symbol: currency.symbol,
                is_default: currency.is_default,
            });
        } else if (!open) {
            reset();
        }
    }, [open, currency, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        if (currency) {
            // Update existing currency
            router.put(currencyRoutes.update(currency.id).url, data, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            // Create new currency
            router.post(currencyRoutes.store().url, data, {
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
            <DialogContent className="sm:max-w-[500px]">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>
                            {currency ? 'Editar Moneda' : 'Nueva Moneda'}
                        </DialogTitle>
                        <DialogDescription>
                            {currency
                                ? 'Actualiza la información de la moneda.'
                                : 'Agrega una nueva moneda al sistema.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Currency Code */}
                        <div className="grid gap-2">
                            <Label htmlFor="code">
                                Código{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="code"
                                placeholder="USD"
                                maxLength={3}
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
                                <p className="text-sm text-destructive">
                                    {errors.code}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                Código ISO-4217 de 3 letras (ej: USD, EUR, DOP)
                            </p>
                        </div>

                        {/* Currency Name */}
                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Nombre{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                placeholder="US Dollar"
                                maxLength={100}
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
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        {/* Currency Symbol */}
                        <div className="grid gap-2">
                            <Label htmlFor="symbol">
                                Símbolo{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="symbol"
                                placeholder="$"
                                maxLength={10}
                                value={data.symbol}
                                onChange={(e) =>
                                    setData('symbol', e.target.value)
                                }
                                className={
                                    errors.symbol ? 'border-destructive' : ''
                                }
                                required
                            />
                            {errors.symbol && (
                                <p className="text-sm text-destructive">
                                    {errors.symbol}
                                </p>
                            )}
                        </div>

                        {/* Is Default */}
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_default"
                                checked={data.is_default}
                                onCheckedChange={(checked) =>
                                    setData('is_default', checked === true)
                                }
                            />
                            <Label
                                htmlFor="is_default"
                                className="cursor-pointer text-sm font-normal"
                            >
                                Establecer como moneda por defecto
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
                            {currency ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
