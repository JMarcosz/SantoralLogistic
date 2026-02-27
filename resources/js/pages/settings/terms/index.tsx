import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { Check, FileText, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

interface Term {
    id: number;
    code: string;
    name: string;
    description: string | null;
    body: string;
    type: string;
    type_label: string;
    scope: string | null;
    is_default: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    terms: Term[];
    types: Array<{ value: string; label: string }>;
    filters: {
        type?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/settings' },
    { title: 'Términos y Condiciones', href: '/settings/terms' },
];

const typeColors: Record<string, string> = {
    payment: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    quote_footer: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    shipping_order_footer: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    invoice_footer: 'bg-violet-500/10 text-violet-400 border-violet-500/30',
};

const emptyForm = {
    code: '',
    name: '',
    description: '',
    body: '',
    type: 'payment',
    scope: '',
    is_default: false,
    is_active: true,
};

export default function TermsIndex({ terms, types, filters }: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    const [dialogOpen, setDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [editingTerm, setEditingTerm] = useState<Term | null>(null);
    const [deletingTerm, setDeletingTerm] = useState<Term | null>(null);
    const [form, setForm] = useState(emptyForm);
    const [typeFilter, setTypeFilter] = useState(filters.type || 'all');

    const openCreate = () => {
        setEditingTerm(null);
        setForm(emptyForm);
        setDialogOpen(true);
    };

    const openEdit = (term: Term) => {
        setEditingTerm(term);
        setForm({
            code: term.code,
            name: term.name,
            description: term.description || '',
            body: term.body,
            type: term.type,
            scope: term.scope || '',
            is_default: term.is_default,
            is_active: term.is_active,
        });
        setDialogOpen(true);
    };

    const openDelete = (term: Term) => {
        setDeletingTerm(term);
        setDeleteDialogOpen(true);
    };

    const handleSubmit = () => {
        if (editingTerm) {
            router.put(`/settings/terms/${editingTerm.id}`, form, {
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            router.post('/settings/terms', form, {
                onSuccess: () => setDialogOpen(false),
            });
        }
    };

    const handleDelete = () => {
        if (deletingTerm) {
            router.delete(`/settings/terms/${deletingTerm.id}`, {
                onSuccess: () => setDeleteDialogOpen(false),
            });
        }
    };

    const handleTypeFilter = (value: string) => {
        setTypeFilter(value);
        const params: Record<string, string> = {};
        if (value !== 'all') params.type = value;
        router.get('/settings/terms', params, { preserveState: true });
    };

    const columns: ColumnDef<Term>[] = [
        {
            accessorKey: 'code',
            header: 'Código',
            cell: ({ row }) => (
                <span className="font-mono font-semibold">
                    {row.getValue('code')}
                </span>
            ),
        },
        {
            accessorKey: 'name',
            header: 'Nombre',
        },
        {
            accessorKey: 'type',
            header: 'Tipo',
            cell: ({ row }) => {
                const type = row.getValue('type') as string;
                const typeLabel = row.original.type_label;
                return (
                    <Badge className={typeColors[type] || 'bg-gray-500/10'}>
                        {typeLabel}
                    </Badge>
                );
            },
        },
        {
            accessorKey: 'is_default',
            header: 'Default',
            cell: ({ row }) =>
                row.getValue('is_default') ? (
                    <Badge className="border-primary/30 bg-primary/10 text-primary">
                        <Check className="mr-1 h-3 w-3" /> Default
                    </Badge>
                ) : (
                    <span className="text-muted-foreground">-</span>
                ),
        },
        {
            accessorKey: 'is_active',
            header: 'Estado',
            cell: ({ row }) =>
                row.getValue('is_active') ? (
                    <Badge className="border-emerald-500/30 bg-emerald-500/10 text-emerald-400">
                        Activo
                    </Badge>
                ) : (
                    <Badge className="border-red-500/30 bg-red-500/10 text-red-400">
                        Inactivo
                    </Badge>
                ),
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Acciones</div>,
            cell: ({ row }) => {
                const term = row.original;
                return (
                    <div className="flex justify-end gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => openEdit(term)}
                        >
                            <Pencil className="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => openDelete(term)}
                            className="text-red-400 hover:text-red-300"
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                );
            },
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Términos y Condiciones" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Flash Messages */}
                {flash?.success && (
                    <div className="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 text-emerald-400">
                        <Check className="h-5 w-5" />
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="flex items-center gap-2 rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-red-400">
                        <X className="h-5 w-5" />
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <FileText className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Términos y Condiciones
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Gestión de términos de pago y condiciones
                                    legales
                                </p>
                            </div>
                        </div>

                        <Button onClick={openCreate} className="shadow-md">
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Término
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <div className="flex items-center gap-3">
                    <Select value={typeFilter} onValueChange={handleTypeFilter}>
                        <SelectTrigger className="w-[220px]">
                            <SelectValue placeholder="Filtrar por tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Todos los tipos</SelectItem>
                            {types.map((t) => (
                                <SelectItem key={t.value} value={t.value}>
                                    {t.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Table */}
                <DataTable columns={columns} data={terms} />
            </div>

            {/* Create/Edit Dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editingTerm ? 'Editar Término' : 'Nuevo Término'}
                        </DialogTitle>
                        <DialogDescription>
                            {editingTerm
                                ? 'Modifica los campos del término'
                                : 'Complete los campos para crear un nuevo término'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="code">Código</Label>
                                <Input
                                    id="code"
                                    value={form.code}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            code: e.target.value.toUpperCase(),
                                        })
                                    }
                                    placeholder="NET30"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type">Tipo</Label>
                                <Select
                                    value={form.type}
                                    onValueChange={(v) =>
                                        setForm({ ...form, type: v })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((t) => (
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
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="name">Nombre</Label>
                            <Input
                                id="name"
                                value={form.name}
                                onChange={(e) =>
                                    setForm({ ...form, name: e.target.value })
                                }
                                placeholder="Pago 30 días"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">
                                Descripción (opcional)
                            </Label>
                            <Input
                                id="description"
                                value={form.description}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        description: e.target.value,
                                    })
                                }
                                placeholder="Descripción interna..."
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="body">Texto del Término</Label>
                            <Textarea
                                id="body"
                                value={form.body}
                                onChange={(e) =>
                                    setForm({ ...form, body: e.target.value })
                                }
                                placeholder="El texto que aparecerá en los documentos..."
                                rows={6}
                            />
                        </div>

                        <div className="flex items-center gap-6">
                            <label className="flex cursor-pointer items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.is_default}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            is_default: e.target.checked,
                                        })
                                    }
                                    className="rounded border-gray-300"
                                />
                                <span className="text-sm">
                                    Término por defecto
                                </span>
                            </label>
                            <label className="flex cursor-pointer items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.is_active}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            is_active: e.target.checked,
                                        })
                                    }
                                    className="rounded border-gray-300"
                                />
                                <span className="text-sm">Activo</span>
                            </label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDialogOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button onClick={handleSubmit}>
                            {editingTerm ? 'Guardar Cambios' : 'Crear Término'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation */}
            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar término?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción no se puede deshacer. El término será
                            eliminado permanentemente.
                            {deletingTerm && (
                                <span className="mt-2 block font-semibold text-foreground">
                                    {deletingTerm.code} - {deletingTerm.name}
                                </span>
                            )}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            className="bg-red-600 hover:bg-red-700"
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
