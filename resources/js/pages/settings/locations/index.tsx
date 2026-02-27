import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
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
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Edit, MapPin, Plus } from 'lucide-react';
import { useState } from 'react';

interface Warehouse {
    id: number;
    name: string;
    code: string;
}

interface LocationType {
    value: string;
    label: string;
}

interface Location {
    id: number;
    warehouse_id: number;
    code: string;
    zone: string | null;
    type: string;
    max_weight_kg: number | null;
    is_active: boolean;
    warehouse: Warehouse;
}

interface Props {
    locations: Location[];
    warehouses: Warehouse[];
    locationTypes: LocationType[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/settings/profile' },
    { title: 'Ubicaciones', href: '/settings/locations' },
];

export default function LocationsIndex({
    locations,
    warehouses,
    locationTypes,
}: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingLocation, setEditingLocation] = useState<Location | null>(
        null,
    );

    const { data, setData, post, put, processing, errors, reset } = useForm({
        warehouse_id: '',
        code: '',
        zone: '',
        type: 'rack',
        max_weight_kg: '',
        is_active: true,
    });

    const openCreate = () => {
        reset();
        setEditingLocation(null);
        setDialogOpen(true);
    };

    const openEdit = (location: Location) => {
        setData({
            warehouse_id: location.warehouse_id.toString(),
            code: location.code,
            zone: location.zone || '',
            type: location.type,
            max_weight_kg: location.max_weight_kg?.toString() || '',
            is_active: location.is_active,
        });
        setEditingLocation(location);
        setDialogOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingLocation) {
            put(`/settings/locations/${editingLocation.id}`, {
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
        } else {
            post('/settings/locations', {
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
        }
    };

    const toggleActive = (location: Location) => {
        router.delete(`/settings/locations/${location.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ubicaciones" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <MapPin className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">Ubicaciones</h1>
                            <p className="text-sm text-muted-foreground">
                                Gestiona las ubicaciones de tus almacenes
                            </p>
                        </div>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nueva Ubicación
                    </Button>
                </div>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Ubicaciones</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Almacén</TableHead>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Zona</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Peso Máx (kg)</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {locations.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No hay ubicaciones
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    locations.map((location) => (
                                        <TableRow key={location.id}>
                                            <TableCell>
                                                {location.warehouse?.name}
                                            </TableCell>
                                            <TableCell className="font-mono font-medium">
                                                {location.code}
                                            </TableCell>
                                            <TableCell>
                                                {location.zone || '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {locationTypes.find(
                                                        (t) =>
                                                            t.value ===
                                                            location.type,
                                                    )?.label || location.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {location.max_weight_kg || '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        location.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {location.is_active
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        openEdit(location)
                                                    }
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        toggleActive(location)
                                                    }
                                                >
                                                    {location.is_active
                                                        ? 'Desactivar'
                                                        : 'Activar'}
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            {/* Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingLocation
                                ? 'Editar Ubicación'
                                : 'Nueva Ubicación'}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="warehouse_id">Almacén *</Label>
                            <Select
                                value={data.warehouse_id}
                                onValueChange={(v) =>
                                    setData('warehouse_id', v)
                                }
                                disabled={!!editingLocation}
                            >
                                <SelectTrigger className="mt-1">
                                    <SelectValue placeholder="Seleccionar..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {warehouses.map((w) => (
                                        <SelectItem
                                            key={w.id}
                                            value={w.id.toString()}
                                        >
                                            {w.name} ({w.code})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.warehouse_id && (
                                <p className="mt-1 text-sm text-red-500">
                                    {errors.warehouse_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="code">Código *</Label>
                                <Input
                                    id="code"
                                    value={data.code}
                                    onChange={(e) =>
                                        setData(
                                            'code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    placeholder="A-01-01"
                                    className="mt-1"
                                />
                                {errors.code && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.code}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="zone">Zona</Label>
                                <Input
                                    id="zone"
                                    value={data.zone}
                                    onChange={(e) =>
                                        setData('zone', e.target.value)
                                    }
                                    placeholder="A, B, C..."
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="type">Tipo *</Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(v) => setData('type', v)}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {locationTypes.map((t) => (
                                            <SelectItem
                                                key={t.value}
                                                value={t.value}
                                            >
                                                {t.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="max_weight_kg">
                                    Peso Máx (kg)
                                </Label>
                                <Input
                                    id="max_weight_kg"
                                    type="number"
                                    value={data.max_weight_kg}
                                    onChange={(e) =>
                                        setData('max_weight_kg', e.target.value)
                                    }
                                    min="0"
                                    step="0.01"
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        {editingLocation && (
                            <div className="flex items-center gap-2">
                                <Switch
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(v) =>
                                        setData('is_active', v)
                                    }
                                />
                                <Label htmlFor="is_active">Activo</Label>
                            </div>
                        )}
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setDialogOpen(false)}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Guardando...' : 'Guardar'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
