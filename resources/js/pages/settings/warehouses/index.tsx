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
import { Edit, Plus, Warehouse as WarehouseIcon } from 'lucide-react';
import { useState } from 'react';

interface Warehouse {
    id: number;
    name: string;
    code: string;
    address: string | null;
    city: string | null;
    country: string | null;
    is_active: boolean;
}

interface Props {
    warehouses: Warehouse[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/settings/profile' },
    { title: 'Almacenes', href: '/settings/warehouses' },
];

export default function WarehousesIndex({ warehouses }: Props) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingWarehouse, setEditingWarehouse] = useState<Warehouse | null>(
        null,
    );

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        code: '',
        address: '',
        city: '',
        country: '',
        is_active: true,
    });

    const openCreate = () => {
        reset();
        setEditingWarehouse(null);
        setDialogOpen(true);
    };

    const openEdit = (warehouse: Warehouse) => {
        setData({
            name: warehouse.name,
            code: warehouse.code,
            address: warehouse.address || '',
            city: warehouse.city || '',
            country: warehouse.country || '',
            is_active: warehouse.is_active,
        });
        setEditingWarehouse(warehouse);
        setDialogOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingWarehouse) {
            put(`/settings/warehouses/${editingWarehouse.id}`, {
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
        } else {
            post('/settings/warehouses', {
                onSuccess: () => {
                    setDialogOpen(false);
                    reset();
                },
            });
        }
    };

    const toggleActive = (warehouse: Warehouse) => {
        router.delete(`/settings/warehouses/${warehouse.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Almacenes" />

            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                            <WarehouseIcon className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">Almacenes</h1>
                            <p className="text-sm text-muted-foreground">
                                Gestiona tus almacenes
                            </p>
                        </div>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nuevo Almacén
                    </Button>
                </div>

                {/* Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Almacenes</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Dirección</TableHead>
                                    <TableHead>Ciudad</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {warehouses.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            No hay almacenes
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    warehouses.map((warehouse) => (
                                        <TableRow key={warehouse.id}>
                                            <TableCell className="font-mono font-medium">
                                                {warehouse.code}
                                            </TableCell>
                                            <TableCell>
                                                {warehouse.name}
                                            </TableCell>
                                            <TableCell>
                                                {warehouse.address || '-'}
                                            </TableCell>
                                            <TableCell>
                                                {warehouse.city || '-'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        warehouse.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {warehouse.is_active
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        openEdit(warehouse)
                                                    }
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        toggleActive(warehouse)
                                                    }
                                                >
                                                    {warehouse.is_active
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
                            {editingWarehouse
                                ? 'Editar Almacén'
                                : 'Nuevo Almacén'}
                        </DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleSubmit} className="space-y-4">
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
                                    placeholder="WH01"
                                    className="mt-1"
                                />
                                {errors.code && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.code}
                                    </p>
                                )}
                            </div>
                            <div>
                                <Label htmlFor="name">Nombre *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Almacén Principal"
                                    className="mt-1"
                                />
                                {errors.name && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="address">Dirección</Label>
                            <Input
                                id="address"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                                className="mt-1"
                            />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <Label htmlFor="city">Ciudad</Label>
                                <Input
                                    id="city"
                                    value={data.city}
                                    onChange={(e) =>
                                        setData('city', e.target.value)
                                    }
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="country">País</Label>
                                <Input
                                    id="country"
                                    value={data.country}
                                    onChange={(e) =>
                                        setData('country', e.target.value)
                                    }
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        {editingWarehouse && (
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
