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

interface Division {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
}

interface Props {
    divisions: Division[];
}

export default function DivisionsIndex({ divisions }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingDivision, setEditingDivision] = useState<Division | null>(
        null,
    );

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm({
            name: '',
            code: '',
            is_active: true,
        });

    const openCreate = () => {
        reset();
        clearErrors();
        setEditingDivision(null);
        setIsCreateOpen(true);
    };

    const openEdit = (division: Division) => {
        reset();
        clearErrors();
        setEditingDivision(division);
        setData({
            name: division.name,
            code: division.code,
            is_active: division.is_active,
        });
        setIsCreateOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingDivision) {
            put(`/divisions/${editingDivision.id}`, {
                onSuccess: () => setIsCreateOpen(false),
            });
        } else {
            post('/divisions', {
                onSuccess: () => setIsCreateOpen(false),
            });
        }
    };

    const handleDelete = (division: Division) => {
        if (confirm('¿Estás seguro de eliminar esta división?')) {
            router.delete(`/divisions/${division.id}`);
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Configuración', href: '/settings' },
                { title: 'Divisiones', href: '/divisions' },
            ]}
        >
            <Head title="Gestión de Divisiones" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Divisiones
                        </h1>
                        <p className="text-muted-foreground">
                            Administra las divisiones de negocio.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nueva División
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
                            {divisions.map((division) => (
                                <TableRow key={division.id}>
                                    <TableCell className="font-mono font-medium">
                                        {division.code}
                                    </TableCell>
                                    <TableCell>{division.name}</TableCell>
                                    <TableCell>
                                        <div
                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none ${
                                                division.is_active
                                                    ? 'bg-emerald-500/10 text-emerald-500'
                                                    : 'bg-slate-500/10 text-slate-500'
                                            }`}
                                        >
                                            {division.is_active
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
                                                    openEdit(division)
                                                }
                                            >
                                                <Edit className="h-4 w-4 text-muted-foreground" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    handleDelete(division)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {divisions.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No hay divisiones registradas.
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
                            {editingDivision
                                ? 'Editar División'
                                : 'Nueva División'}
                        </DialogTitle>
                        <DialogDescription>
                            Complete los datos de la división.
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
                                placeholder="Ej. EXP"
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
                                placeholder="Ej. Exportación"
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
                                {editingDivision ? 'Guardar Cambios' : 'Crear'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
