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
import { type Contact } from '@/types';
import { router, useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

interface ContactFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    contact?: Contact | null;
    customerId: number;
}

export default function ContactFormDialog({
    open,
    onOpenChange,
    contact,
    customerId,
}: ContactFormDialogProps) {
    const { data, setData, processing, errors, reset } = useForm({
        name: contact?.name || '',
        email: contact?.email || '',
        phone: contact?.phone || '',
        position: contact?.position || '',
        contact_type: contact?.contact_type || 'general',
        is_primary: contact?.is_primary ?? false,
        notes: contact?.notes || '',
        is_active: contact?.is_active ?? true,
    });

    useEffect(() => {
        if (open && contact) {
            setData({
                name: contact.name,
                email: contact.email || '',
                phone: contact.phone || '',
                position: contact.position || '',
                contact_type: contact.contact_type || 'general',
                is_primary: contact.is_primary,
                notes: contact.notes || '',
                is_active: contact.is_active,
            });
        } else if (!open) {
            reset();
        }
    }, [open, contact, setData, reset]);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const payload = {
            ...data,
            contact_type: data.contact_type || null,
        };

        if (contact) {
            router.put(
                `/crm/customers/${customerId}/contacts/${contact.id}`,
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
            router.post(`/crm/customers/${customerId}/contacts`, payload, {
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
                            {contact ? 'Editar Contacto' : 'Nuevo Contacto'}
                        </DialogTitle>
                        <DialogDescription>
                            {contact
                                ? 'Modifica la información del contacto.'
                                : 'Agrega un nuevo contacto para este cliente.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Name */}
                        <div className="grid gap-1.5">
                            <Label htmlFor="name" className="text-sm">
                                Nombre{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                placeholder="Nombre completo"
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

                        {/* Email & Phone */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="email" className="text-sm">
                                    Correo Electrónico
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="correo@empresa.com"
                                    maxLength={255}
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    className={
                                        errors.email ? 'border-destructive' : ''
                                    }
                                />
                                {errors.email && (
                                    <p className="text-xs text-destructive">
                                        {errors.email}
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

                        {/* Position & Type */}
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-1.5">
                                <Label htmlFor="position" className="text-sm">
                                    Cargo
                                </Label>
                                <Input
                                    id="position"
                                    placeholder="Gerente de Compras"
                                    maxLength={100}
                                    value={data.position}
                                    onChange={(e) =>
                                        setData('position', e.target.value)
                                    }
                                />
                            </div>

                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="contact_type"
                                    className="text-sm"
                                >
                                    Tipo de Contacto
                                </Label>
                                <Select
                                    value={data.contact_type || 'general'}
                                    onValueChange={(value) =>
                                        setData(
                                            'contact_type',
                                            value as
                                                | 'general'
                                                | 'billing'
                                                | 'operations'
                                                | 'sales',
                                        )
                                    }
                                >
                                    <SelectTrigger id="contact_type">
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="general">
                                            General
                                        </SelectItem>
                                        <SelectItem value="billing">
                                            Facturación
                                        </SelectItem>
                                        <SelectItem value="operations">
                                            Operaciones
                                        </SelectItem>
                                        <SelectItem value="sales">
                                            Ventas
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        {/* Notes */}
                        <div className="grid gap-1.5">
                            <Label htmlFor="notes" className="text-sm">
                                Notas
                            </Label>
                            <Textarea
                                id="notes"
                                placeholder="Notas adicionales sobre este contacto..."
                                maxLength={5000}
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                rows={2}
                            />
                        </div>

                        {/* Checkboxes */}
                        <div className="flex flex-wrap gap-6 pt-2">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_primary"
                                    checked={data.is_primary}
                                    onCheckedChange={(checked) =>
                                        setData('is_primary', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="is_primary"
                                    className="cursor-pointer text-sm font-normal"
                                >
                                    Contacto principal
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
                                    Activo
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
                            {contact ? 'Actualizar' : 'Crear'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
