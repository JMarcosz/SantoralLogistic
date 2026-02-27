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

interface Project {
    id: number;
    name: string;
    code: string;
    is_active: boolean;
}

interface Props {
    projects: Project[];
}

export default function ProjectsIndex({ projects }: Props) {
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [editingProject, setEditingProject] = useState<Project | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } =
        useForm({
            name: '',
            code: '',
            is_active: true,
        });

    const openCreate = () => {
        reset();
        clearErrors();
        setEditingProject(null);
        setIsCreateOpen(true);
    };

    const openEdit = (project: Project) => {
        reset();
        clearErrors();
        setEditingProject(project);
        setData({
            name: project.name,
            code: project.code,
            is_active: project.is_active,
        });
        setIsCreateOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingProject) {
            put(`/projects/${editingProject.id}`, {
                onSuccess: () => setIsCreateOpen(false),
            });
        } else {
            post('/projects', {
                onSuccess: () => setIsCreateOpen(false),
            });
        }
    };

    const handleDelete = (project: Project) => {
        if (confirm('¿Estás seguro de eliminar este proyecto?')) {
            router.delete(`/projects/${project.id}`);
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Configuración', href: '/settings' },
                { title: 'Proyectos', href: '/projects' },
            ]}
        >
            <Head title="Gestión de Proyectos" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">
                            Proyectos
                        </h1>
                        <p className="text-muted-foreground">
                            Administra los proyectos activos.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nuevo Proyecto
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
                            {projects.map((project) => (
                                <TableRow key={project.id}>
                                    <TableCell className="font-mono font-medium">
                                        {project.code}
                                    </TableCell>
                                    <TableCell>{project.name}</TableCell>
                                    <TableCell>
                                        <div
                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none ${
                                                project.is_active
                                                    ? 'bg-emerald-500/10 text-emerald-500'
                                                    : 'bg-slate-500/10 text-slate-500'
                                            }`}
                                        >
                                            {project.is_active
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
                                                    openEdit(project)
                                                }
                                            >
                                                <Edit className="h-4 w-4 text-muted-foreground" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    handleDelete(project)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                            {projects.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No hay proyectos registrados.
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
                            {editingProject
                                ? 'Editar Proyecto'
                                : 'Nuevo Proyecto'}
                        </DialogTitle>
                        <DialogDescription>
                            Complete los datos del proyecto.
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
                                placeholder="Ej. PRJ-001"
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
                                placeholder="Ej. Proyecto Alpha"
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
                                {editingProject ? 'Guardar Cambios' : 'Crear'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
