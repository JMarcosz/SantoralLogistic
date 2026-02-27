import { Button } from '@/components/ui/button';
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
import { Head, router, useForm } from '@inertiajs/react';
import { Edit, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Carrier {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
}

interface Props {
    carriers: Carrier[];
}

export default function CarriersIndex({ carriers }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingCarrier, setEditingCarrier] = useState<Carrier | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm({
            name: '',
            code: '',
            is_active: true,
        });

    const openCreate = () => {
        reset();
        clearErrors();
        setEditingCarrier(null);
        setIsCreateOpen(true);
    };

    const openEdit = (carrier: Carrier) => {
        reset();
        clearErrors();
        setEditingCarrier(carrier);
        setData({
            name: carrier.name,
            code: carrier.code,
            is_active: carrier.is_active,
        });
        setIsCreateOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingCarrier) {
            put(`/carriers/${editingCarrier.id}`, {
                onSuccess: () => setIsCreateOpen(false),
            });
        } else {
            post('/carriers', {
                onSuccess: () => setIsCreateOpen(false),
            });
        }
    };

    const handleDelete = (carrier: Carrier) => {
        if (confirm('¿Estás seguro de eliminar este carrier?')) {
            router.delete(`/carriers/${carrier.id}`);
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Configuración', href: '/settings' },
                { title: 'Carriers', href: '/carriers' },
            ]}
        >
            <Head title="Gestión de Carriers" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Carriers
                        </h1>
                        <p className="text-muted-foreground">
                            Administra los transportistas disponibles.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nuevo Carrier
                    </Button>
                </div>

                <div className="rounded-md border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Código</TableHead>
                                <TableHead>Nombre</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">
                                    Acciones
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {carriers.map((carrier) => (
                                <TableRow key={carrier.id}>
                                    <TableCell className="font-mono font-medium">
                                        {carrier.code}
                                    </TableCell>
                                    <TableCell>{carrier.name}</TableCell>
                                    <TableCell>
                                        <div
                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none ${
                                                carrier.is_active
                                                    ? 'bg-emerald-500/10 text-emerald-500'
                                                    : 'bg-slate-500/10 text-slate-500'
                                            }`}
                                        >
                                            {carrier.is_active
                                                ? 'Activo'
                                                : 'Inactivo'}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    openEdit(carrier)
                                                }
                                            >
                                                <Edit className="h-4 w-4 text-muted-foreground" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    handleDelete(carrier)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {carriers.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No hay carriers registrados.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingCarrier
                                ? 'Editar Carrier'
                                : 'Nuevo Carrier'}
                        </DialogTitle>
                        <DialogDescription>
                            Complete los datos del carrier.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="code">Código</Label>
                            <Input
                                id="code"
                                value={data.code}
                                onChange={(e) =>
                                    setData(
                                        'code',
                                        e.target.value.toUpperCase(),
                                    )
                                }
                                placeholder="Ej. MAERSK"
                                maxLength={20}
                                disabled={processing}
                            />
                            {errors.code && (
                                <p className="text-sm text-destructive">
                                    {errors.code}
                                </p>
                            )}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Nombre</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                placeholder="Ej. Maersk Line"
                                disabled={processing}
                            />
                            {errors.name && (
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            <Switch
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) =>
                                    setData('is_active', checked)
                                }
                                disabled={processing}
                            />
                            <Label htmlFor="is_active">Activo</Label>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsCreateOpen(false)}
                                disabled={processing}
                            >
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {editingCarrier ? 'Guardar Cambios' : 'Crear'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
