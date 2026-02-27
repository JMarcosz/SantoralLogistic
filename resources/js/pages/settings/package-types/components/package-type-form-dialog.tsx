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
import { type PackageType } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface PackageTypeFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    packageType?: PackageType | null;
}

export default function PackageTypeFormDialog({
    open,
    onOpenChange,
    packageType,
}: PackageTypeFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        code: packageType?.code || '',
        name: packageType?.name || '',
        description: packageType?.description || '',
        category: packageType?.category || '',
        length_cm: packageType?.length_cm?.toString() || '',
        width_cm: packageType?.width_cm?.toString() || '',
        height_cm: packageType?.height_cm?.toString() || '',
        max_weight_kg: packageType?.max_weight_kg?.toString() || '',
        is_container: packageType?.is_container ?? false,
        is_active: packageType?.is_active ?? true,
    });

    useEffect(() => {
        if (open && packageType) {
            setData({
                code: packageType.code,
                name: packageType.name,
                description: packageType.description || '',
                category: packageType.category || '',
                length_cm: packageType.length_cm?.toString() || '',
                width_cm: packageType.width_cm?.toString() || '',
                height_cm: packageType.height_cm?.toString() || '',
                max_weight_kg: packageType.max_weight_kg?.toString() || '',
                is_container: packageType.is_container,
                is_active: packageType.is_active,
            });
        } else if (!open) {
            reset();
        }
    }, [open, packageType, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const payload = {
            ...data,
            length_cm: data.length_cm || null,
            width_cm: data.width_cm || null,
            height_cm: data.height_cm || null,
            max_weight_kg: data.max_weight_kg || null,
            category: data.category || null,
        };

        if (packageType) {
            router.put(`/settings/package-types/${packageType.id}`, payload, {
                preserveScroll: true,
                onSuccess: () => {
                    onOpenChange(false);
                    reset();
                },
            });
        } else {
            router.post('/settings/package-types', payload, {
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
                            {packageType
                                ? 'Editar Tipo de Paquete'
                                : 'Nuevo Tipo de Paquete'}
                        </DialogTitle>
                        <DialogDescription>
                            {packageType
                                ? 'Actualiza la información del tipo de paquete.'
                                : 'Define un nuevo tipo de empaque para warehouse y shipping.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Row 1: Code, Name, Category */}
                        <div className="grid grid-cols-3 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="code" className="text-sm">
                                    Código{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="code"
                                    placeholder="BOX"
                                    maxLength={20}
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
                                    placeholder="Caja Estándar"
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
                                    <p className="text-xs text-destructive">
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="category" className="text-sm">
                                    Categoría
                                </Label>
                                <Select
                                    value={data.category}
                                    onValueChange={(value) =>
                                        setData('category', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="category"
                                        className={
                                            errors.category
                                                ? 'border-destructive'
                                                : ''
                                        }
                                    >
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="box">
                                            Caja
                                        </SelectItem>
                                        <SelectItem value="pallet">
                                            Pallet
                                        </SelectItem>
                                        <SelectItem value="container">
                                            Contenedor
                                        </SelectItem>
                                        <SelectItem value="envelope">
                                            Sobre
                                        </SelectItem>
                                        <SelectItem value="other">
                                            Otro
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.category && (
                                    <p className="text-xs text-destructive">
                                        {errors.category}
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Row 2: Description */}
                        <div className="grid gap-1.5">
                            <Label htmlFor="description" className="text-sm">
                                Descripción
                            </Label>
                            <Textarea
                                id="description"
                                placeholder="Descripción del tipo de paquete..."
                                maxLength={1000}
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                rows={2}
                            />
                        </div>

                        {/* Row 3: Dimensions */}
                        <div className="grid grid-cols-4 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="length_cm" className="text-sm">
                                    Largo (cm)
                                </Label>
                                <Input
                                    id="length_cm"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.length_cm}
                                    onChange={(e) =>
                                        setData('length_cm', e.target.value)
                                    }
                                    className={
                                        errors.length_cm
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="width_cm" className="text-sm">
                                    Ancho (cm)
                                </Label>
                                <Input
                                    id="width_cm"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.width_cm}
                                    onChange={(e) =>
                                        setData('width_cm', e.target.value)
                                    }
                                    className={
                                        errors.width_cm
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="height_cm" className="text-sm">
                                    Alto (cm)
                                </Label>
                                <Input
                                    id="height_cm"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.height_cm}
                                    onChange={(e) =>
                                        setData('height_cm', e.target.value)
                                    }
                                    className={
                                        errors.height_cm
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="max_weight_kg"
                                    className="text-sm"
                                >
                                    Peso Máx. (kg)
                                </Label>
                                <Input
                                    id="max_weight_kg"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    value={data.max_weight_kg}
                                    onChange={(e) =>
                                        setData('max_weight_kg', e.target.value)
                                    }
                                    className={
                                        errors.max_weight_kg
                                            ? 'border-destructive'
                                            : ''
                                    }
                                />
                            </div>
                        </div>

                        {/* Row 4: Checkboxes */}
                        <div className="flex flex-wrap gap-6 pt-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_container"
                                    checked={data.is_container}
                                    onCheckedChange={(checked) =>
                                        setData(
                                            'is_container',
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor="is_container"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    Es contenedor marítimo
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
                                    Tipo activo
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
                            {packageType ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
