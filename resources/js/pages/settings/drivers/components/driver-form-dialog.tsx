/* eslint-disable react-hooks/exhaustive-deps */
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import driverRoutes from '@/routes/settings/drivers';
import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';

// Define Driver interface locally since backend model doesn't exist yet
export interface Driver {
    id: number;
    name: string;
    phone: string | null;
    email: string | null;
    license_number: string | null;
    vehicle_plate: string | null;
    is_active: boolean;
}

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    driver: Driver | null; // If null, create mode
}

export default function DriverFormDialog({
    open,
    onOpenChange,
    driver,
}: Props) {
    const isEdit = !!driver;

    const form = useForm({
        name: '',
        phone: '',
        email: '',
        license_number: '',
        vehicle_plate: '',
        is_active: true,
    });

    useEffect(() => {
        if (open) {
            form.reset();
            form.clearErrors();
            if (driver) {
                form.setData({
                    name: driver.name,
                    phone: driver.phone ?? '',
                    email: driver.email ?? '',
                    license_number: driver.license_number ?? '',
                    vehicle_plate: driver.vehicle_plate ?? '',
                    is_active: driver.is_active,
                });
            } else {
                form.setData({
                    name: '',
                    phone: '',
                    email: '',
                    license_number: '',
                    vehicle_plate: '',
                    is_active: true,
                });
            }
        }
    }, [open, driver]);

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit && driver) {
            form.put(driverRoutes.update(driver.id).url, {
                onSuccess: () => onOpenChange(false),
            });
        } else {
            form.post(driverRoutes.store().url, {
                onSuccess: () => onOpenChange(false),
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>
                        {isEdit ? 'Editar Conductor' : 'Nuevo Conductor'}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={onSubmit} className="space-y-4">
                    {/* Name */}
                    <div className="space-y-2">
                        <Label htmlFor="name">Nombre Completo *</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            disabled={form.processing}
                        />
                        {form.errors.name && (
                            <p className="text-sm text-destructive">
                                {form.errors.name}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {/* Phone */}
                        <div className="space-y-2">
                            <Label htmlFor="phone">Teléfono</Label>
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(e) =>
                                    form.setData('phone', e.target.value)
                                }
                                disabled={form.processing}
                            />
                            {form.errors.phone && (
                                <p className="text-sm text-destructive">
                                    {form.errors.phone}
                                </p>
                            )}
                        </div>

                        {/* Email */}
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                disabled={form.processing}
                            />
                            {form.errors.email && (
                                <p className="text-sm text-destructive">
                                    {form.errors.email}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        {/* License Number */}
                        <div className="space-y-2">
                            <Label htmlFor="license_number">Licencia</Label>
                            <Input
                                id="license_number"
                                value={form.data.license_number}
                                onChange={(e) =>
                                    form.setData(
                                        'license_number',
                                        e.target.value,
                                    )
                                }
                                disabled={form.processing}
                            />
                            {form.errors.license_number && (
                                <p className="text-sm text-destructive">
                                    {form.errors.license_number}
                                </p>
                            )}
                        </div>

                        {/* Vehicle Plate */}
                        <div className="space-y-2">
                            <Label htmlFor="vehicle_plate">
                                Placa Vehículo
                            </Label>
                            <Input
                                id="vehicle_plate"
                                value={form.data.vehicle_plate}
                                onChange={(e) =>
                                    form.setData(
                                        'vehicle_plate',
                                        e.target.value,
                                    )
                                }
                                disabled={form.processing}
                            />
                            {form.errors.vehicle_plate && (
                                <p className="text-sm text-destructive">
                                    {form.errors.vehicle_plate}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Status */}
                    <div className="flex items-center justify-between rounded-lg border p-4">
                        <div className="space-y-0.5">
                            <Label className="text-base">Estado</Label>
                            <div className="text-sm text-muted-foreground">
                                {form.data.is_active ? 'Activo' : 'Inactivo'}
                            </div>
                        </div>
                        <Switch
                            checked={form.data.is_active}
                            onCheckedChange={(checked) =>
                                form.setData('is_active', checked)
                            }
                            disabled={form.processing}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={form.processing}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Guardando...' : 'Guardar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
