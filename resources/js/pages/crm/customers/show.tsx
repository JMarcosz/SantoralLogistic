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
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type Customer } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CreditCard,
    Edit,
    ExternalLink,
    FileText,
    Globe,
    Mail,
    MapPin,
    Package,
    Phone,
    Plus,
    Star,
    Trash2,
    User,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import ContactFormDialog from './components/contact-form-dialog';
import CustomerFormDialog from './components/customer-form-dialog';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface Props {
    customer: Customer;
    currencies: Currency[];
    can: {
        update: boolean;
        delete: boolean;
        createContact: boolean;
        updateContact: boolean;
        deleteContact: boolean;
    };
}

const statusLabels: Record<string, string> = {
    prospect: 'Prospecto',
    active: 'Activo',
    inactive: 'Inactivo',
};

const statusColors: Record<string, string> = {
    prospect: 'bg-amber-500/10 text-amber-500 border-amber-500/30',
    active: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
    inactive: 'bg-slate-500/10 text-slate-500 border-slate-500/30',
};

const contactTypeLabels: Record<string, string> = {
    general: 'General',
    billing: 'Facturación',
    operations: 'Operaciones',
    sales: 'Ventas',
};

const contactTypeColors: Record<string, string> = {
    general: 'bg-slate-500/10 text-slate-500 border-slate-500/30',
    billing: 'bg-violet-500/10 text-violet-500 border-violet-500/30',
    operations: 'bg-sky-500/10 text-sky-500 border-sky-500/30',
    sales: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/30',
};

export default function CustomerShow({ customer, currencies, can }: Props) {
    const [formDialogOpen, setFormDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    // Contact state
    const [contactFormOpen, setContactFormOpen] = useState(false);
    const [selectedContact, setSelectedContact] = useState<Contact | null>(
        null,
    );
    const [deleteContactDialogOpen, setDeleteContactDialogOpen] =
        useState(false);
    const [contactToDelete, setContactToDelete] = useState<Contact | null>(
        null,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'CRM', href: '/crm/customers' },
        { title: 'Clientes', href: '/crm/customers' },
        { title: customer.name, href: `/crm/customers/${customer.id}` },
    ];

    const handleEdit = () => {
        setFormDialogOpen(true);
    };

    const handleDeleteConfirm = () => {
        router.delete(`/crm/customers/${customer.id}`, {
            onSuccess: () => {
                setDeleteDialogOpen(false);
            },
        });
    };

    // Contact handlers
    const handleCreateContact = () => {
        setSelectedContact(null);
        setContactFormOpen(true);
    };

    const handleEditContact = (contact: Contact) => {
        setSelectedContact(contact);
        setContactFormOpen(true);
    };

    const handleDeleteContactClick = (contact: Contact) => {
        setContactToDelete(contact);
        setDeleteContactDialogOpen(true);
    };

    const handleDeleteContactConfirm = () => {
        if (contactToDelete) {
            router.delete(
                `/crm/customers/${customer.id}/contacts/${contactToDelete.id}`,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setDeleteContactDialogOpen(false);
                        setContactToDelete(null);
                    },
                },
            );
        }
    };

    const contacts = customer.contacts || [];
    const activeContacts = contacts.filter((c) => c.is_active);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Cliente: ${customer.name}`} />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-start justify-between">
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" size="icon" asChild>
                                <Link href="/crm/customers">
                                    <ArrowLeft className="h-5 w-5" />
                                </Link>
                            </Button>
                            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                                <Building2 className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl font-bold tracking-tight">
                                        {customer.name}
                                    </h1>
                                    <Badge
                                        className={
                                            statusColors[customer.status]
                                        }
                                    >
                                        {statusLabels[customer.status]}
                                    </Badge>
                                    {!customer.is_active && (
                                        <Badge variant="secondary">
                                            Desactivado
                                        </Badge>
                                    )}
                                </div>
                                <div className="mt-1 flex items-center gap-3 text-sm text-muted-foreground">
                                    {customer.code && (
                                        <span className="font-mono">
                                            {customer.code}
                                        </span>
                                    )}
                                    {customer.tax_id && (
                                        <>
                                            <span>•</span>
                                            <span>RNC: {customer.tax_id}</span>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex gap-2">
                            {can.update && (
                                <Button variant="outline" onClick={handleEdit}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar
                                </Button>
                            )}
                            {can.delete && (
                                <Button
                                    variant="destructive"
                                    onClick={() => setDeleteDialogOpen(true)}
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Eliminar
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Content Grid */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Info */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Contact Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <User className="h-5 w-5 text-primary" />
                                    Información de Contacto
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <p className="text-sm text-muted-foreground">
                                        Email de Facturación
                                    </p>
                                    {customer.email_billing ? (
                                        <a
                                            href={`mailto:${customer.email_billing}`}
                                            className="flex items-center gap-2 text-sm hover:text-primary"
                                        >
                                            <Mail className="h-4 w-4" />
                                            {customer.email_billing}
                                        </a>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            -
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-1">
                                    <p className="text-sm text-muted-foreground">
                                        Teléfono
                                    </p>
                                    {customer.phone ? (
                                        <a
                                            href={`tel:${customer.phone}`}
                                            className="flex items-center gap-2 text-sm hover:text-primary"
                                        >
                                            <Phone className="h-4 w-4" />
                                            {customer.phone}
                                        </a>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            -
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-1 sm:col-span-2">
                                    <p className="text-sm text-muted-foreground">
                                        Sitio Web
                                    </p>
                                    {customer.website ? (
                                        <a
                                            href={customer.website}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center gap-2 text-sm hover:text-primary"
                                        >
                                            <Globe className="h-4 w-4" />
                                            {customer.website}
                                            <ExternalLink className="h-3 w-3" />
                                        </a>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            -
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Addresses */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <MapPin className="h-5 w-5 text-primary" />
                                    Direcciones
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-6 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <p className="text-sm font-medium">
                                        Dirección de Facturación
                                    </p>
                                    {customer.billing_address ? (
                                        <div className="text-sm text-muted-foreground">
                                            <p>{customer.billing_address}</p>
                                            <p>
                                                {[customer.city, customer.state]
                                                    .filter(Boolean)
                                                    .join(', ')}
                                            </p>
                                            <p>{customer.country}</p>
                                        </div>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            No especificada
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <p className="text-sm font-medium">
                                        Dirección de Envío
                                    </p>
                                    {customer.shipping_address ? (
                                        <p className="text-sm text-muted-foreground">
                                            {customer.shipping_address}
                                        </p>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Igual a facturación
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Contacts Section */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <Users className="h-5 w-5 text-primary" />
                                        Contactos
                                        <Badge variant="secondary">
                                            {activeContacts.length}
                                        </Badge>
                                    </CardTitle>
                                    {can.createContact && (
                                        <Button
                                            size="sm"
                                            onClick={handleCreateContact}
                                        >
                                            <Plus className="mr-1 h-4 w-4" />
                                            Agregar
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                {contacts.length === 0 ? (
                                    <div className="py-8 text-center text-muted-foreground">
                                        <Users className="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        <p>No hay contactos registrados</p>
                                        {can.createContact && (
                                            <Button
                                                variant="link"
                                                onClick={handleCreateContact}
                                                className="mt-2"
                                            >
                                                Agregar el primer contacto
                                            </Button>
                                        )}
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>
                                                        Nombre
                                                    </TableHead>
                                                    <TableHead>
                                                        Contacto
                                                    </TableHead>
                                                    <TableHead>Cargo</TableHead>
                                                    <TableHead>Tipo</TableHead>
                                                    <TableHead>
                                                        Estado
                                                    </TableHead>
                                                    <TableHead className="text-right">
                                                        Acciones
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {contacts.map((contact) => (
                                                    <TableRow
                                                        key={contact.id}
                                                        className={
                                                            !contact.is_active
                                                                ? 'opacity-50'
                                                                : ''
                                                        }
                                                    >
                                                        <TableCell>
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium">
                                                                    {
                                                                        contact.name
                                                                    }
                                                                </span>
                                                                {contact.is_primary && (
                                                                    <Star className="h-4 w-4 fill-amber-500 text-amber-500" />
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex flex-col gap-0.5 text-sm">
                                                                {contact.email && (
                                                                    <a
                                                                        href={`mailto:${contact.email}`}
                                                                        className="flex items-center gap-1 text-muted-foreground hover:text-primary"
                                                                    >
                                                                        <Mail className="h-3 w-3" />
                                                                        {
                                                                            contact.email
                                                                        }
                                                                    </a>
                                                                )}
                                                                {contact.phone && (
                                                                    <a
                                                                        href={`tel:${contact.phone}`}
                                                                        className="flex items-center gap-1 text-muted-foreground hover:text-primary"
                                                                    >
                                                                        <Phone className="h-3 w-3" />
                                                                        {
                                                                            contact.phone
                                                                        }
                                                                    </a>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            {contact.position ||
                                                                '-'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {contact.contact_type && (
                                                                <Badge
                                                                    className={
                                                                        contactTypeColors[
                                                                            contact
                                                                                .contact_type
                                                                        ]
                                                                    }
                                                                >
                                                                    {
                                                                        contactTypeLabels[
                                                                            contact
                                                                                .contact_type
                                                                        ]
                                                                    }
                                                                </Badge>
                                                            )}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge
                                                                variant={
                                                                    contact.is_active
                                                                        ? 'default'
                                                                        : 'secondary'
                                                                }
                                                                className={
                                                                    contact.is_active
                                                                        ? 'bg-emerald-500/10 text-emerald-500'
                                                                        : ''
                                                                }
                                                            >
                                                                {contact.is_active
                                                                    ? 'Activo'
                                                                    : 'Inactivo'}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <div className="flex justify-end gap-1">
                                                                {can.updateContact && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        onClick={() =>
                                                                            handleEditContact(
                                                                                contact,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Edit className="h-4 w-4" />
                                                                    </Button>
                                                                )}
                                                                {can.deleteContact && (
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        onClick={() =>
                                                                            handleDeleteContactClick(
                                                                                contact,
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Notes */}
                        {customer.notes && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <FileText className="h-5 w-5 text-primary" />
                                        Notas
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                        {customer.notes}
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Financial Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <CreditCard className="h-5 w-5 text-primary" />
                                    Información Financiera
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-1">
                                    <p className="text-sm text-muted-foreground">
                                        Moneda Preferida
                                    </p>
                                    <p className="font-medium">
                                        {customer.currency
                                            ? `${customer.currency.code} - ${customer.currency.name}`
                                            : 'No especificada'}
                                    </p>
                                </div>

                                <Separator />

                                <div className="space-y-1">
                                    <p className="text-sm text-muted-foreground">
                                        Límite de Crédito
                                    </p>
                                    <p className="font-mono text-lg font-semibold">
                                        {customer.credit_limit
                                            ? `${customer.currency?.symbol || '$'}${Number(customer.credit_limit).toLocaleString('en-US', { minimumFractionDigits: 2 })}`
                                            : 'Sin límite'}
                                    </p>
                                </div>

                                <Separator />

                                <div className="space-y-1">
                                    <p className="text-sm text-muted-foreground">
                                        Términos de Pago
                                    </p>
                                    <p className="font-medium">
                                        {customer.payment_terms ||
                                            'No especificados'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Quick Links - Placeholders */}
                        <Card className="border-dashed">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg text-muted-foreground">
                                    <Package className="h-5 w-5" />
                                    Accesos Rápidos
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    disabled
                                >
                                    <FileText className="mr-2 h-4 w-4" />
                                    Cotizaciones
                                    <Badge
                                        variant="secondary"
                                        className="ml-auto"
                                    >
                                        Próximamente
                                    </Badge>
                                </Button>

                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    disabled
                                >
                                    <Package className="mr-2 h-4 w-4" />
                                    Órdenes de Envío
                                    <Badge
                                        variant="secondary"
                                        className="ml-auto"
                                    >
                                        Próximamente
                                    </Badge>
                                </Button>
                            </CardContent>
                        </Card>

                        {/* Metadata */}
                        <Card>
                            <CardContent className="pt-6">
                                <div className="space-y-2 text-xs text-muted-foreground">
                                    <div className="flex justify-between">
                                        <span>Creado:</span>
                                        <span>
                                            {new Date(
                                                customer.created_at,
                                            ).toLocaleDateString('es-DO', {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric',
                                            })}
                                        </span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Actualizado:</span>
                                        <span>
                                            {new Date(
                                                customer.updated_at,
                                            ).toLocaleDateString('es-DO', {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric',
                                            })}
                                        </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Customer Form Dialog */}
            <CustomerFormDialog
                open={formDialogOpen}
                onOpenChange={setFormDialogOpen}
                customer={customer}
                currencies={currencies}
            />

            {/* Customer Delete Confirmation */}
            <AlertDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará al cliente{' '}
                            <span className="font-semibold">
                                {customer.name}
                            </span>
                            . Esta acción no se puede deshacer.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDeleteConfirm}>
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Contact Form Dialog */}
            <ContactFormDialog
                open={contactFormOpen}
                onOpenChange={setContactFormOpen}
                contact={selectedContact}
                customerId={customer.id}
            />

            {/* Contact Delete Confirmation */}
            <AlertDialog
                open={deleteContactDialogOpen}
                onOpenChange={setDeleteContactDialogOpen}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar contacto?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará al contacto{' '}
                            <span className="font-semibold">
                                {contactToDelete?.name}
                            </span>
                            .
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDeleteContactConfirm}>
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
