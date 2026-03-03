/* eslint-disable @typescript-eslint/no-unused-vars */
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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
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
import { type BreadcrumbItem, type Company } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Ban,
    Calendar,
    Check,
    CheckCircle,
    Circle,
    Clock,
    Copy,
    Edit,
    ExternalLink,
    FileText,
    Globe,
    Lock,
    MapPin,
    Package,
    Plane,
    Play,
    Plus,
    Printer,
    Receipt,
    Ship,
    Truck,
    Upload,
    User,
    X,
} from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { InventoryPicker } from '../inventory/components/inventory-picker';
import ChargesSection, { type Charge } from './components/charges-section';

interface Milestone {
    id: number;
    code: string;
    label: string;
    happened_at: string;
    location: string | null;
    remarks: string | null;
    created_by?: { id: number; name: string } | null;
    created_at: string;
}

interface ShippingOrder {
    id: number;
    order_number: string;
    customer?: {
        id: number;
        name: string;
        code: string | null;
        tax_id?: string | null;
        billing_address?: string | null;
        phone?: string | null;
    };
    contact?: {
        id: number;
        name: string;
        email: string | null;
        phone: string | null;
    };
    shipper?: {
        id: number;
        name: string;
        code: string | null;
    } | null;
    consignee?: {
        id: number;
        name: string;
        code: string | null;
    } | null;
    origin_port?: { id: number; code: string; name: string; country: string };
    destination_port?: {
        id: number;
        code: string;
        name: string;
        country: string;
    };
    transport_mode?: { id: number; code: string; name: string };
    service_type?: { id: number; code: string; name: string };
    currency?: { id: number; code: string; symbol: string };
    quote?: {
        id: number;
        quote_number: string;
        status: string;
        total_amount: number;
        created_at: string;
    };
    created_by?: { id: number; name: string };
    milestones?: Milestone[];
    footer_terms?: {
        id: number;
        code: string;
        name: string;
    } | null;
    ocean_shipment?: {
        id: number;
        mbl_number: string | null;
        hbl_number: string | null;
        carrier_name: string | null;
        vessel_name: string | null;
        voyage_number: string | null;
        container_details: Record<string, unknown>[] | null;
    } | null;
    air_shipment?: {
        id: number;
        mawb_number: string | null;
        hawb_number: string | null;
        airline_name: string | null;
        flight_number: string | null;
    } | null;
    status: string;
    is_active: boolean;
    total_amount: number;
    total_pieces: number | null;
    total_weight_kg: number | null;
    total_volume_cbm: number | null;
    pickup_date: string | null;
    delivery_date: string | null;
    planned_departure_at: string | null;
    planned_arrival_at: string | null;
    actual_departure_at: string | null;
    actual_arrival_at: string | null;
    notes: string | null;
    created_at: string;
    pickup_orders?: {
        id: number;
        status: string;
        scheduled_date: string | null;
        driver?: { id: number; name: string } | null;
    }[];
    delivery_orders?: {
        id: number;
        status: string;
        scheduled_date: string | null;
        driver?: { id: number; name: string } | null;
    }[];
    charges?: Charge[];
}

interface Document {
    id: number;
    type: string;
    original_name: string;
    mime_type: string | null;
    size: number | null;
    uploaded_by?: { id: number; name: string } | null;
    created_at: string;
}

interface MilestoneCodeOption {
    value: string;
    label: string;
}

interface DocumentTypeOption {
    value: string;
    label: string;
}

interface Currency {
    id: number;
    code: string;
    symbol: string;
    name: string;
}

interface OptionItem {
    value: string;
    label: string;
}

interface ProductService {
    id: number;
    code: string;
    name: string;
    description: string | null;
    type: string;
    uom: string | null;
    default_currency_id: number | null;
    default_unit_price: number | null;
    default_currency?: {
        id: number;
        code: string;
        symbol: string;
    } | null;
}

interface Props {
    order: ShippingOrder & { documents?: Document[] };
    company: Company | null;
    milestoneCodes: MilestoneCodeOption[];
    documentTypes: DocumentTypeOption[];
    currencies: Currency[];
    chargeTypes: OptionItem[];
    chargeBases: OptionItem[];
    productsServices: ProductService[];
    publicTrackingUrl: string | null;
    publicTrackingEnabled: boolean;
    activePreInvoice: {
        id: number;
        number: string;
        status: string;
    } | null;
    can: {
        update: boolean;
        delete: boolean;
        changeStatus: boolean;
        book: boolean;
        startTransit: boolean;
        arrive: boolean;
        deliver: boolean;
        close: boolean;
        cancel: boolean;
        addMilestone: boolean;
        uploadDocument: boolean;
        deleteDocument: boolean;
        managePublicTracking: boolean;
        generatePreInvoice: boolean;
        manageCharges: boolean;
        createWarehouseOrder: boolean;
        reserveInventory: boolean;
    };
    warehouses: { id: number; name: string; code: string }[];
    hasInventoryReservations: boolean;
    reservationsCount: number;
    inventoryReservations: {
        id: number;
        sku: string;
        qty_reserved: number;
        warehouse: string;
    }[];
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    booked: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    in_transit: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    arrived: 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30',
    delivered: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    closed: 'bg-gray-500/10 text-gray-400 border-gray-500/30',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    booked: 'Reservado',
    in_transit: 'En Tránsito',
    arrived: 'Llegado',
    delivered: 'Entregado',
    closed: 'Cerrado',
    cancelled: 'Cancelado',
};

const modeIcons: Record<string, React.ReactNode> = {
    AIR: <Plane className="h-5 w-5" />,
    OCEAN: <Ship className="h-5 w-5" />,
    GROUND: <Truck className="h-5 w-5" />,
};

// Milestone Form Dialog Component
function MilestoneFormDialog({
    orderId,
    milestoneCodes,
}: {
    orderId: number;
    milestoneCodes: MilestoneCodeOption[];
}) {
    const [open, setOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [form, setForm] = useState({
        code: '',
        happened_at: new Date().toISOString().slice(0, 16),
        location: '',
        remarks: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(`/shipping-orders/${orderId}/milestones`, form, {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                setForm({
                    code: '',
                    happened_at: new Date().toISOString().slice(0, 16),
                    location: '',
                    remarks: '',
                });
            },
            onFinish: () => setIsSubmitting(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Plus className="mr-2 h-4 w-4" />
                    Agregar
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Agregar Milestone</DialogTitle>
                        <DialogDescription>
                            Registrar un nuevo evento de tracking
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="code">Tipo de Evento *</Label>
                            <Select
                                value={form.code}
                                onValueChange={(value) =>
                                    setForm({ ...form, code: value })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar evento" />
                                </SelectTrigger>
                                <SelectContent>
                                    {milestoneCodes.map((code) => (
                                        <SelectItem
                                            key={code.value}
                                            value={code.value}
                                        >
                                            {code.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="happened_at">Fecha y Hora *</Label>
                            <Input
                                id="happened_at"
                                type="datetime-local"
                                value={form.happened_at}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        happened_at: e.target.value,
                                    })
                                }
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="location">Ubicación</Label>
                            <Input
                                id="location"
                                placeholder="Ciudad, puerto, almacén..."
                                value={form.location}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        location: e.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="remarks">Observaciones</Label>
                            <Textarea
                                id="remarks"
                                placeholder="Notas adicionales..."
                                value={form.remarks}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        remarks: e.target.value,
                                    })
                                }
                                rows={3}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={!form.code || isSubmitting}
                        >
                            {isSubmitting ? 'Guardando...' : 'Guardar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// Document Upload Dialog Component
function DocumentUploadDialog({
    orderId,
    documentTypes,
}: {
    orderId: number;
    documentTypes: DocumentTypeOption[];
}) {
    const [open, setOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [type, setType] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!file || !type) return;

        setIsSubmitting(true);

        const formData = new FormData();
        formData.append('type', type);
        formData.append('file', file);

        router.post(`/shipping-orders/${orderId}/documents`, formData, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setOpen(false);
                setType('');
                setFile(null);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
            onFinish: () => setIsSubmitting(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Upload className="mr-2 h-4 w-4" />
                    Subir
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Subir Documento</DialogTitle>
                        <DialogDescription>
                            Adjuntar AWB, BL, factura u otros documentos
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label htmlFor="doc-type">
                                Tipo de Documento *
                            </Label>
                            <Select value={type} onValueChange={setType}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    {documentTypes.map((docType) => (
                                        <SelectItem
                                            key={docType.value}
                                            value={docType.value}
                                        >
                                            {docType.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="doc-file">Archivo *</Label>
                            <Input
                                id="doc-file"
                                type="file"
                                ref={fileInputRef}
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif"
                                onChange={(e) =>
                                    setFile(e.target.files?.[0] || null)
                                }
                                required
                            />
                            <p className="text-xs text-muted-foreground">
                                Máx. 10MB. PDF, DOC, XLS, imágenes.
                            </p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={!type || !file || isSubmitting}
                        >
                            {isSubmitting ? 'Subiendo...' : 'Subir'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// Public Tracking Section Component
function PublicTrackingSection({
    orderId,
    enabled,
    url,
}: {
    orderId: number;
    enabled: boolean;
    url: string | null;
}) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [copied, setCopied] = useState(false);

    const handleEnable = () => {
        setIsSubmitting(true);
        router.post(
            `/shipping-orders/${orderId}/enable-public-tracking`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const handleDisable = () => {
        setIsSubmitting(true);
        router.post(
            `/shipping-orders/${orderId}/disable-public-tracking`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const handleCopy = async () => {
        if (url) {
            try {
                await navigator.clipboard.writeText(url);
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            } catch {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            }
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                    <Globe className="h-5 w-5" />
                    Tracking Público
                </CardTitle>
                <CardDescription>
                    Enlace compartible para clientes y partners
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {enabled && url ? (
                    <>
                        <div className="flex items-center gap-2">
                            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20">
                                <Check className="h-3 w-3 text-emerald-400" />
                            </div>
                            <span className="text-sm font-medium text-emerald-400">
                                Tracking público activado
                            </span>
                        </div>

                        <div className="flex gap-2">
                            <Input
                                value={url}
                                readOnly
                                className="flex-1 bg-muted/50 font-mono text-xs"
                            />
                            <Button
                                variant="outline"
                                size="icon"
                                onClick={handleCopy}
                                title={copied ? '¡Copiado!' : 'Copiar URL'}
                            >
                                {copied ? (
                                    <Check className="h-4 w-4 text-emerald-400" />
                                ) : (
                                    <Copy className="h-4 w-4" />
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                asChild
                                title="Abrir en nueva pestaña"
                            >
                                <a
                                    href={url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <ExternalLink className="h-4 w-4" />
                                </a>
                            </Button>
                        </div>

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleDisable}
                            disabled={isSubmitting}
                            className="w-full text-red-400 hover:bg-red-500/10 hover:text-red-400"
                        >
                            {isSubmitting
                                ? 'Desactivando...'
                                : 'Desactivar Tracking Público'}
                        </Button>
                    </>
                ) : (
                    <>
                        <div className="flex items-center gap-2">
                            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-slate-500/20">
                                <X className="h-3 w-3 text-slate-400" />
                            </div>
                            <span className="text-sm text-muted-foreground">
                                Tracking público desactivado
                            </span>
                        </div>

                        <p className="text-sm text-muted-foreground">
                            Activa el tracking público para generar un enlace
                            que podrás compartir con clientes y partners para
                            que vean el estado del envío sin necesidad de
                            iniciar sesión.
                        </p>

                        <Button
                            onClick={handleEnable}
                            disabled={isSubmitting}
                            className="w-full"
                        >
                            <Globe className="mr-2 h-4 w-4" />
                            {isSubmitting
                                ? 'Activando...'
                                : 'Activar Tracking Público'}
                        </Button>
                    </>
                )}
            </CardContent>
        </Card>
    );
}

// Warehouse Order Dialog Component
function WarehouseOrderDialog({
    orderId,
    warehouses,
    hasReservations,
    reservationsCount,
}: {
    orderId: number;
    warehouses: { id: number; name: string; code: string }[];
    hasReservations: boolean;
    reservationsCount: number;
}) {
    const [open, setOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [warehouseId, setWarehouseId] = useState('');
    const [notes, setNotes] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!warehouseId) return;

        setIsSubmitting(true);
        router.post(
            `/shipping-orders/${orderId}/warehouse-order`,
            {
                warehouse_id: warehouseId,
                notes,
            },
            {
                onSuccess: () => {
                    setOpen(false);
                    setWarehouseId('');
                    setNotes('');
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button className="bg-orange-600 hover:bg-orange-700">
                    <Package className="mr-2 h-4 w-4" />
                    Crear Orden Almacén
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Crear Orden de Almacén</DialogTitle>
                        <DialogDescription>
                            Generar una orden de picking/packing para esta orden
                            de envío
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        {/* Reservation Status */}
                        {hasReservations ? (
                            <div className="flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm text-emerald-400">
                                <CheckCircle className="h-4 w-4" />
                                <span>
                                    {reservationsCount} reserva(s) de inventario
                                    disponible(s)
                                </span>
                            </div>
                        ) : (
                            <div className="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm">
                                <div className="flex items-center gap-2 text-amber-400">
                                    <Circle className="h-4 w-4" />
                                    <span className="font-medium">
                                        Sin reservas de inventario
                                    </span>
                                </div>
                                <p className="mt-2 text-muted-foreground">
                                    Para crear una orden de almacén, primero
                                    debe reservar los productos en{' '}
                                    <Link
                                        href="/inventory"
                                        className="text-amber-400 underline hover:text-amber-300"
                                    >
                                        Inventario
                                    </Link>
                                    .
                                </p>
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="warehouse">Almacén *</Label>
                            <Select
                                value={warehouseId}
                                onValueChange={setWarehouseId}
                                disabled={!hasReservations}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar almacén" />
                                </SelectTrigger>
                                <SelectContent>
                                    {warehouses.map((w) => (
                                        <SelectItem
                                            key={w.id}
                                            value={String(w.id)}
                                        >
                                            {w.code} - {w.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="wo-notes">Notas (opcional)</Label>
                            <Textarea
                                id="wo-notes"
                                placeholder="Instrucciones especiales para el almacén..."
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                rows={3}
                                disabled={!hasReservations}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                !warehouseId || isSubmitting || !hasReservations
                            }
                            className="bg-orange-600 hover:bg-orange-700"
                        >
                            {isSubmitting ? 'Creando...' : 'Crear Orden'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// Reserve Inventory Dialog Component
function ReserveInventoryDialog({
    orderId,
    customerId,
    reservations,
    onReserve,
}: {
    orderId: number;
    customerId: number;
    reservations: {
        id: number;
        sku: string;
        qty_reserved: number;
        warehouse: string;
    }[];
    onReserve: () => void;
}) {
    const [open, setOpen] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [lines, setLines] = useState<{ sku: string; qty: string }[]>([
        { sku: '', qty: '' },
    ]);

    const addLine = () => {
        setLines([...lines, { sku: '', qty: '' }]);
    };

    const removeLine = (index: number) => {
        if (lines.length > 1) {
            setLines(lines.filter((_, i) => i !== index));
        }
    };

    const updateLine = (index: number, field: 'sku' | 'qty', value: string) => {
        const newLines = [...lines];
        newLines[index][field] = value;
        setLines(newLines);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const validLines = lines.filter(
            (l) => l.sku.trim() && parseFloat(l.qty) > 0,
        );
        if (validLines.length === 0) return;

        setIsSubmitting(true);
        router.post(
            `/shipping-orders/${orderId}/reserve-inventory`,
            {
                lines: validLines.map((l) => ({
                    sku: l.sku.trim(),
                    qty: parseFloat(l.qty),
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setOpen(false);
                    setLines([{ sku: '', qty: '' }]);
                    onReserve();
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const handleRelease = () => {
        if (!confirm('¿Está seguro de liberar todas las reservas?')) return;
        setIsSubmitting(true);
        router.delete(`/shipping-orders/${orderId}/reservations`, {
            preserveScroll: true,
            onSuccess: () => {
                onReserve();
            },
            onFinish: () => setIsSubmitting(false),
        });
    };

    const hasValidLines = lines.some(
        (l) => l.sku.trim() && parseFloat(l.qty) > 0,
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    className="border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10"
                >
                    <Package className="mr-2 h-4 w-4" />
                    Reservar Inventario
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Reservar Inventario</DialogTitle>
                    <DialogDescription>
                        Reserve productos del inventario del cliente para esta
                        orden
                    </DialogDescription>
                </DialogHeader>

                {/* Current Reservations */}
                {reservations.length > 0 && (
                    <div className="rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-3">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-sm font-medium text-emerald-400">
                                Reservas Actuales ({reservations.length})
                            </span>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={handleRelease}
                                disabled={isSubmitting}
                                className="h-7 text-xs text-red-400 hover:bg-red-500/10 hover:text-red-300"
                            >
                                Liberar Todas
                            </Button>
                        </div>
                        <div className="max-h-32 space-y-1 overflow-y-auto">
                            {reservations.map((r) => (
                                <div
                                    key={r.id}
                                    className="flex justify-between text-sm"
                                >
                                    <span className="font-mono">{r.sku}</span>
                                    <span className="text-muted-foreground">
                                        {r.qty_reserved} • {r.warehouse}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    <div className="space-y-3 py-4">
                        <div className="text-sm font-medium">
                            Agregar Reservas
                        </div>
                        {lines.map((line, index) => (
                            <div key={index} className="flex items-start gap-2">
                                <div className="flex-1">
                                    <InventoryPicker
                                        customerId={customerId}
                                        value={line.sku}
                                        onChange={(val) =>
                                            updateLine(index, 'sku', val)
                                        }
                                        onSelect={(item) => {
                                            // Optional: Auto-fill max qty?
                                            // updateLine(index, 'qty', String(item.available));
                                        }}
                                    />
                                </div>
                                <Input
                                    type="number"
                                    placeholder="Qty"
                                    step="0.01"
                                    min="0"
                                    value={line.qty}
                                    onChange={(e) =>
                                        updateLine(index, 'qty', e.target.value)
                                    }
                                    className="w-24"
                                />
                                {lines.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeLine(index)}
                                        className="h-9 w-9 text-red-400"
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        ))}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={addLine}
                            className="w-full"
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar Línea
                        </Button>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cerrar
                        </Button>
                        <Button
                            type="submit"
                            disabled={!hasValidLines || isSubmitting}
                            className="bg-emerald-600 hover:bg-emerald-700"
                        >
                            {isSubmitting ? 'Reservando...' : 'Reservar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ShippingOrderShow({
    order,
    company: _company,
    milestoneCodes,
    documentTypes,
    currencies,
    chargeTypes,
    chargeBases,
    productsServices,
    publicTrackingUrl,
    publicTrackingEnabled,
    activePreInvoice,
    warehouses,
    hasInventoryReservations,
    reservationsCount,
    inventoryReservations,
    can,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            { title: 'Órdenes de Envío', href: '/shipping-orders' },
            { title: order.order_number, href: `/shipping-orders/${order.id}` },
        ],
        [order],
    );

    const currencySymbol = order.currency?.symbol || '$';

    // Confirmation dialog state
    const [confirmDialog, setConfirmDialog] = useState<{
        open: boolean;
        action: string;
        title: string;
        description: string;
        variant: 'default' | 'destructive';
    }>({
        open: false,
        action: '',
        title: '',
        description: '',
        variant: 'default',
    });

    const actionConfigs: Record<
        string,
        {
            title: string;
            description: string;
            variant: 'default' | 'destructive';
        }
    > = {
        book: {
            title: '¿Reservar orden?',
            description:
                'La orden será marcada como reservada con el transportista.',
            variant: 'default',
        },
        'start-transit': {
            title: '¿Iniciar tránsito?',
            description:
                'La orden será marcada como en tránsito. Se registrará la fecha de salida actual.',
            variant: 'default',
        },
        arrive: {
            title: '¿Marcar como llegada?',
            description:
                'La orden será marcada como llegada al destino. Se registrará la fecha de llegada actual.',
            variant: 'default',
        },
        deliver: {
            title: '¿Marcar como entregada?',
            description:
                'La orden será marcada como entregada al cliente final.',
            variant: 'default',
        },
        close: {
            title: '¿Cerrar orden?',
            description: 'La orden será cerrada. Esta acción es final.',
            variant: 'default',
        },
        cancel: {
            title: '¿Cancelar orden?',
            description:
                'La orden será cancelada. Esta acción es irreversible.',
            variant: 'destructive',
        },
    };

    const openConfirmDialog = (action: string) => {
        const config = actionConfigs[action] || {
            title: '¿Confirmar?',
            description: '',
            variant: 'default' as const,
        };
        setConfirmDialog({
            open: true,
            action,
            title: config.title,
            description: config.description,
            variant: config.variant,
        });
    };

    const executeAction = () => {
        if (!confirmDialog.action) return;

        router.post(
            `/shipping-orders/${order.id}/${confirmDialog.action}`,
            {},
            {
                preserveScroll: true,
                onFinish: () =>
                    setConfirmDialog({ ...confirmDialog, open: false }),
            },
        );
    };

    const formatDate = (date: string | null) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('es-DO', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    };

    const formatDateTime = (date: string | null) => {
        if (!date) return '-';
        return new Date(date).toLocaleString('es-DO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatNumber = (num: number | null) => {
        if (num === null || num === undefined) return '-';
        return Number(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Orden ${order.order_number}`} />

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
                                <Ship className="h-7 w-7 text-primary-foreground" />
                            </div>
                            <div>
                                <div className="flex items-center gap-3">
                                    <h1 className="text-2xl font-bold tracking-tight">
                                        {order.order_number}
                                    </h1>
                                    <Badge
                                        className={statusColors[order.status]}
                                    >
                                        {statusLabels[order.status] ||
                                            order.status}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    Creada el {formatDate(order.created_at)}
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap gap-2">
                            {/* Edit Button */}
                            {can.update && (
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/shipping-orders/${order.id}/edit`}
                                    >
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Link>
                                </Button>
                            )}

                            {/* Print PDF Button */}
                            <Button variant="outline" asChild>
                                <a
                                    href={`/shipping-orders/${order.id}/print`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Printer className="mr-2 h-4 w-4" />
                                    Imprimir / PDF
                                </a>
                            </Button>

                            {/* Book Button */}
                            {can.book && (
                                <Button
                                    className="bg-blue-600 hover:bg-blue-700"
                                    onClick={() => openConfirmDialog('book')}
                                >
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Reservar
                                </Button>
                            )}

                            {/* Start Transit Button */}
                            {can.startTransit && (
                                <Button
                                    className="bg-amber-600 hover:bg-amber-700"
                                    onClick={() =>
                                        openConfirmDialog('start-transit')
                                    }
                                >
                                    <Play className="mr-2 h-4 w-4" />
                                    Iniciar Tránsito
                                </Button>
                            )}

                            {/* Arrive Button */}
                            {can.arrive && (
                                <Button
                                    className="bg-cyan-600 hover:bg-cyan-700"
                                    onClick={() => openConfirmDialog('arrive')}
                                >
                                    <MapPin className="mr-2 h-4 w-4" />
                                    Marcar Llegada
                                </Button>
                            )}

                            {/* Deliver Button */}
                            {can.deliver && (
                                <Button
                                    className="bg-emerald-600 hover:bg-emerald-700"
                                    onClick={() => openConfirmDialog('deliver')}
                                >
                                    <Check className="mr-2 h-4 w-4" />
                                    Marcar Entrega
                                </Button>
                            )}

                            {/* Close Button */}
                            {can.close && (
                                <Button
                                    variant="outline"
                                    onClick={() => openConfirmDialog('close')}
                                >
                                    <Lock className="mr-2 h-4 w-4" />
                                    Cerrar
                                </Button>
                            )}

                            {/* Cancel Button */}
                            {can.cancel && (
                                <Button
                                    variant="destructive"
                                    onClick={() => openConfirmDialog('cancel')}
                                >
                                    <Ban className="mr-2 h-4 w-4" />
                                    Cancelar
                                </Button>
                            )}

                            {/* Pre-Invoice Button/Link */}
                            {activePreInvoice ? (
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/pre-invoices/${activePreInvoice.id}`}
                                    >
                                        <Receipt className="mr-2 h-4 w-4" />
                                        Pre-Factura {activePreInvoice.number}
                                    </Link>
                                </Button>
                            ) : can.generatePreInvoice ? (
                                <Button
                                    className="bg-violet-600 hover:bg-violet-700"
                                    onClick={() => {
                                        if (
                                            confirm(
                                                '¿Deseas generar una Pre-Factura para esta orden?',
                                            )
                                        ) {
                                            router.post(
                                                `/shipping-orders/${order.id}/pre-invoices`,
                                            );
                                        }
                                    }}
                                >
                                    <Receipt className="mr-2 h-4 w-4" />
                                    Generar Pre-Factura
                                </Button>
                            ) : null}

                            {/* Reserve Inventory Button */}
                            {can.reserveInventory &&
                                order.customer &&
                                (order.status === 'booked' ||
                                    order.status === 'in_transit') && (
                                    <ReserveInventoryDialog
                                        orderId={order.id}
                                        customerId={order.customer.id}
                                        reservations={inventoryReservations}
                                        onReserve={() => router.reload()}
                                    />
                                )}

                            {/* Warehouse Order Button */}
                            {can.createWarehouseOrder &&
                                warehouses.length > 0 && (
                                    <WarehouseOrderDialog
                                        orderId={order.id}
                                        warehouses={warehouses}
                                        hasReservations={
                                            hasInventoryReservations
                                        }
                                        reservationsCount={reservationsCount}
                                    />
                                )}
                        </div>
                    </div>

                    {/* Quote Reference */}
                    {order.quote && (
                        <div className="mt-4 rounded-lg border border-blue-500/30 bg-blue-500/10 p-3 text-sm">
                            <span className="text-blue-400">
                                📄 Origen:{' '}
                                <Link
                                    href={`/quotes/${order.quote.id}`}
                                    className="font-semibold underline"
                                >
                                    {order.quote.quote_number}
                                </Link>
                            </span>
                        </div>
                    )}

                    {/* Footer Terms Info */}
                    {order.footer_terms && (
                        <div className="mt-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-3 text-sm">
                            <div className="flex items-center gap-2 text-emerald-400">
                                <FileText className="h-4 w-4" />
                                <span>
                                    Términos:{' '}
                                    <span className="font-semibold">
                                        {order.footer_terms.name}
                                    </span>
                                    <span className="ml-2 rounded bg-emerald-500/20 px-2 py-0.5 text-xs">
                                        {order.footer_terms.code}
                                    </span>
                                </span>
                            </div>
                        </div>
                    )}
                </div>

                {/* Main Grid */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Customer Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <User className="h-5 w-5" />
                                Cliente
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <p className="text-lg font-semibold">
                                    {order.customer?.name}
                                </p>
                                {order.customer?.code && (
                                    <p className="text-sm text-muted-foreground">
                                        Código: {order.customer.code}
                                    </p>
                                )}
                                {order.customer?.tax_id && (
                                    <p className="text-sm text-muted-foreground">
                                        RNC: {order.customer.tax_id}
                                    </p>
                                )}
                            </div>
                            {order.contact && (
                                <div className="border-t pt-3">
                                    <p className="text-sm font-medium">
                                        Contacto:
                                    </p>
                                    <p className="text-sm">
                                        {order.contact.name}
                                    </p>
                                    {order.contact.email && (
                                        <p className="text-sm text-muted-foreground">
                                            {order.contact.email}
                                        </p>
                                    )}
                                    {order.contact.phone && (
                                        <p className="text-sm text-muted-foreground">
                                            {order.contact.phone}
                                        </p>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Shipper/Consignee - Partes del Embarque */}
                    {(order.shipper || order.consignee) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <User className="h-5 w-5" />
                                    Partes del Embarque
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    {order.shipper && (
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">
                                                Shipper (Exportador)
                                            </p>
                                            <p className="font-medium">
                                                {order.shipper.code
                                                    ? `${order.shipper.code} - ${order.shipper.name}`
                                                    : order.shipper.name}
                                            </p>
                                        </div>
                                    )}
                                    {order.consignee && (
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">
                                                Consignee (Importador)
                                            </p>
                                            <p className="font-medium">
                                                {order.consignee.code
                                                    ? `${order.consignee.code} - ${order.consignee.name}`
                                                    : order.consignee.name}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Service Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Package className="h-5 w-5" />
                                Detalles del Servicio
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-muted-foreground">
                                        Moneda
                                    </p>
                                    <p className="font-medium">
                                        {order.currency?.code} ({currencySymbol}
                                        )
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Total
                                    </p>
                                    <p className="font-mono font-semibold text-primary">
                                        {currencySymbol}
                                        {formatNumber(order.total_amount)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Modo
                                    </p>
                                    <div className="flex items-center gap-2">
                                        {modeIcons[
                                            order.transport_mode?.code || ''
                                        ] || <Plane className="h-4 w-4" />}
                                        <span className="font-medium">
                                            {order.transport_mode?.name}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">
                                        Servicio
                                    </p>
                                    <p className="font-medium">
                                        {order.service_type?.name}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Ocean Shipment Details */}
                {order.ocean_shipment && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Ship className="h-5 w-5" />
                                Detalles Marítimos
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        MBL (Master B/L)
                                    </p>
                                    <p className="font-mono font-medium">
                                        {order.ocean_shipment.mbl_number || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        HBL (House B/L)
                                    </p>
                                    <p className="font-mono font-medium">
                                        {order.ocean_shipment.hbl_number || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Naviera
                                    </p>
                                    <p className="font-medium">
                                        {order.ocean_shipment.carrier_name ||
                                            '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Buque
                                    </p>
                                    <p className="font-medium">
                                        {order.ocean_shipment.vessel_name ||
                                            '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Viaje
                                    </p>
                                    <p className="font-medium">
                                        {order.ocean_shipment.voyage_number ||
                                            '-'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Air Shipment Details */}
                {order.air_shipment && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Plane className="h-5 w-5" />
                                Detalles Aéreos
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        MAWB (Master AWB)
                                    </p>
                                    <p className="font-mono font-medium">
                                        {order.air_shipment.mawb_number || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        HAWB (House AWB)
                                    </p>
                                    <p className="font-mono font-medium">
                                        {order.air_shipment.hawb_number || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Aerolínea
                                    </p>
                                    <p className="font-medium">
                                        {order.air_shipment.airline_name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Vuelo
                                    </p>
                                    <p className="font-medium">
                                        {order.air_shipment.flight_number ||
                                            '-'}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Lane */}
                <Card>
                    <CardContent className="py-6">
                        <div className="flex items-center justify-center gap-6 text-center">
                            <div>
                                <div className="mb-2 flex items-center justify-center gap-2">
                                    <MapPin className="h-4 w-4 text-muted-foreground" />
                                    <span className="text-xs text-muted-foreground">
                                        Origen
                                    </span>
                                </div>
                                <p className="text-2xl font-bold text-primary">
                                    {order.origin_port?.code}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {order.origin_port?.name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {order.origin_port?.country}
                                </p>
                            </div>
                            <div className="flex flex-col items-center gap-1">
                                {modeIcons[
                                    order.transport_mode?.code || ''
                                ] || (
                                        <Plane className="h-6 w-6 text-muted-foreground" />
                                    )}
                                <ArrowRight className="h-8 w-8 text-muted-foreground" />
                            </div>
                            <div>
                                <div className="mb-2 flex items-center justify-center gap-2">
                                    <MapPin className="h-4 w-4 text-muted-foreground" />
                                    <span className="text-xs text-muted-foreground">
                                        Destino
                                    </span>
                                </div>
                                <p className="text-2xl font-bold text-primary">
                                    {order.destination_port?.code}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {order.destination_port?.name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {order.destination_port?.country}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Dates Section */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <Calendar className="h-5 w-5" />
                            Fechas de Tracking
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="rounded-lg border p-4">
                                <p className="text-xs text-muted-foreground">
                                    Salida Planificada
                                </p>
                                <p className="mt-1 font-semibold">
                                    {formatDateTime(order.planned_departure_at)}
                                </p>
                            </div>
                            <div className="rounded-lg border p-4">
                                <p className="text-xs text-muted-foreground">
                                    Salida Real
                                </p>
                                <p className="mt-1 font-semibold">
                                    {formatDateTime(order.actual_departure_at)}
                                </p>
                            </div>
                            <div className="rounded-lg border p-4">
                                <p className="text-xs text-muted-foreground">
                                    Llegada Planificada
                                </p>
                                <p className="mt-1 font-semibold">
                                    {formatDateTime(order.planned_arrival_at)}
                                </p>
                            </div>
                            <div className="rounded-lg border p-4">
                                <p className="text-xs text-muted-foreground">
                                    Llegada Real
                                </p>
                                <p className="mt-1 font-semibold">
                                    {formatDateTime(order.actual_arrival_at)}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Cargo Details */}
                {(order.total_pieces ||
                    order.total_weight_kg ||
                    order.total_volume_cbm) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">
                                    Detalles de Carga
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-3 gap-6 text-center">
                                    {order.total_pieces && (
                                        <div>
                                            <p className="text-2xl font-bold">
                                                {order.total_pieces}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Piezas
                                            </p>
                                        </div>
                                    )}
                                    {order.total_weight_kg && (
                                        <div>
                                            <p className="text-2xl font-bold">
                                                {formatNumber(
                                                    order.total_weight_kg,
                                                )}{' '}
                                                kg
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Peso
                                            </p>
                                        </div>
                                    )}
                                    {order.total_volume_cbm && (
                                        <div>
                                            <p className="text-2xl font-bold">
                                                {formatNumber(
                                                    order.total_volume_cbm,
                                                )}{' '}
                                                CBM
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                Volumen
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                {/* Placeholders for future features */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Public Tracking Section */}
                    {can.managePublicTracking && (
                        <PublicTrackingSection
                            orderId={order.id}
                            enabled={publicTrackingEnabled}
                            url={publicTrackingUrl}
                        />
                    )}

                    {/* Milestones Timeline */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <Clock className="h-5 w-5" />
                                        Milestones de Tracking
                                    </CardTitle>
                                    <CardDescription>
                                        Seguimiento de eventos del envío
                                    </CardDescription>
                                </div>
                                {can.addMilestone && (
                                    <MilestoneFormDialog
                                        orderId={order.id}
                                        milestoneCodes={milestoneCodes}
                                    />
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {order.milestones && order.milestones.length > 0 ? (
                                <div className="relative">
                                    {/* Timeline line */}
                                    <div className="absolute top-2 bottom-2 left-3 w-px bg-border" />

                                    {/* Timeline items */}
                                    <div className="space-y-4">
                                        {order.milestones.map(
                                            (milestone, index) => (
                                                <div
                                                    key={milestone.id}
                                                    className="relative flex gap-4 pl-8"
                                                >
                                                    {/* Timeline dot */}
                                                    <div className="absolute left-0 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                                        {index === 0 ? (
                                                            <Check className="h-3 w-3" />
                                                        ) : (
                                                            <Circle className="h-2 w-2 fill-current" />
                                                        )}
                                                    </div>

                                                    {/* Content */}
                                                    <div className="flex-1 rounded-lg border bg-card/50 p-3">
                                                        <div className="flex items-start justify-between gap-2">
                                                            <div>
                                                                <p className="font-medium">
                                                                    {
                                                                        milestone.label
                                                                    }
                                                                </p>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {formatDateTime(
                                                                        milestone.happened_at,
                                                                    )}
                                                                </p>
                                                            </div>
                                                            {milestone.location && (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="shrink-0"
                                                                >
                                                                    <MapPin className="mr-1 h-3 w-3" />
                                                                    {
                                                                        milestone.location
                                                                    }
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        {milestone.remarks && (
                                                            <p className="mt-2 text-sm text-muted-foreground">
                                                                {
                                                                    milestone.remarks
                                                                }
                                                            </p>
                                                        )}
                                                        {milestone.created_by && (
                                                            <p className="mt-1 text-xs text-muted-foreground">
                                                                Por:{' '}
                                                                {
                                                                    milestone
                                                                        .created_by
                                                                        .name
                                                                }
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className="flex h-32 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/20">
                                    <p className="text-sm text-muted-foreground">
                                        Sin milestones registrados
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Documents Section */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <FileText className="h-5 w-5" />
                                        Documentos
                                    </CardTitle>
                                    <CardDescription>
                                        AWB, BL, y otros documentos
                                    </CardDescription>
                                </div>
                                {can.uploadDocument && (
                                    <DocumentUploadDialog
                                        orderId={order.id}
                                        documentTypes={documentTypes}
                                    />
                                )}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {order.documents && order.documents.length > 0 ? (
                                <div className="space-y-2">
                                    {order.documents.map((doc) => (
                                        <div
                                            key={doc.id}
                                            className="flex items-center justify-between rounded-lg border p-3"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                                    <FileText className="h-5 w-5 text-primary" />
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <Badge
                                                            variant="outline"
                                                            className="text-xs"
                                                        >
                                                            {doc.type}
                                                        </Badge>
                                                        <p className="text-sm font-medium">
                                                            {doc.original_name}
                                                        </p>
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">
                                                        {doc.uploaded_by
                                                            ?.name &&
                                                            `Por ${doc.uploaded_by.name} • `}
                                                        {formatDateTime(
                                                            doc.created_at,
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                asChild
                                            >
                                                <a
                                                    href={`/shipping-orders/${order.id}/documents/${doc.id}`}
                                                    download
                                                >
                                                    Descargar
                                                </a>
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="flex h-32 items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/20">
                                    <p className="text-sm text-muted-foreground">
                                        Sin documentos adjuntos
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Notes */}
                {order.notes && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Notas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                {order.notes}
                            </p>
                        </CardContent>
                    </Card>
                )}

                {/* Charges Section */}
                <ChargesSection
                    orderId={order.id}
                    charges={order.charges || []}
                    currencies={currencies}
                    chargeTypes={chargeTypes}
                    chargeBases={chargeBases}
                    productsServices={productsServices}
                    orderCurrencyCode={order.currency?.code || 'USD'}
                    canManage={can.manageCharges}
                />

                {/* Pickup & Delivery Orders Section */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Truck className="h-5 w-5" />
                                    Pickup & Delivery
                                </CardTitle>
                                <CardDescription>
                                    Órdenes de recogida y entrega vinculadas
                                </CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={`/pickup-orders/create?shipping_order_id=${order.id}`}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Recogida
                                    </Link>
                                </Button>
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={`/delivery-orders/create?shipping_order_id=${order.id}`}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Entrega
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            {/* Pickups */}
                            <div className="rounded-lg border p-4">
                                <h4 className="mb-3 flex items-center gap-2 font-medium">
                                    <Truck className="h-4 w-4 text-primary" />
                                    Recogidas (
                                    {order.pickup_orders?.length || 0})
                                </h4>
                                {order.pickup_orders &&
                                    order.pickup_orders.length > 0 ? (
                                    <div className="space-y-2">
                                        {order.pickup_orders.map((po) => (
                                            <Link
                                                key={po.id}
                                                href={`/pickup-orders/${po.id}`}
                                                className="flex items-center justify-between rounded-lg border p-2 hover:bg-muted/50"
                                            >
                                                <div>
                                                    <span className="font-medium">
                                                        #{po.id}
                                                    </span>
                                                    <span className="ml-2 text-sm text-muted-foreground">
                                                        {po.scheduled_date ||
                                                            'Sin fecha'}
                                                    </span>
                                                </div>
                                                <Badge
                                                    className={
                                                        po.status ===
                                                            'completed'
                                                            ? 'bg-green-100 text-green-800'
                                                            : po.status ===
                                                                'in_progress'
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : po.status ===
                                                                    'assigned'
                                                                    ? 'bg-blue-100 text-blue-800'
                                                                    : 'bg-gray-100 text-gray-800'
                                                    }
                                                >
                                                    {po.status}
                                                </Badge>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No hay recogidas programadas
                                    </p>
                                )}
                            </div>

                            {/* Deliveries */}
                            <div className="rounded-lg border p-4">
                                <h4 className="mb-3 flex items-center gap-2 font-medium">
                                    <Package className="h-4 w-4 text-green-600" />
                                    Entregas (
                                    {order.delivery_orders?.length || 0})
                                </h4>
                                {order.delivery_orders &&
                                    order.delivery_orders.length > 0 ? (
                                    <div className="space-y-2">
                                        {order.delivery_orders.map((de) => (
                                            <Link
                                                key={de.id}
                                                href={`/delivery-orders/${de.id}`}
                                                className="flex items-center justify-between rounded-lg border p-2 hover:bg-muted/50"
                                            >
                                                <div>
                                                    <span className="font-medium">
                                                        #{de.id}
                                                    </span>
                                                    <span className="ml-2 text-sm text-muted-foreground">
                                                        {de.scheduled_date ||
                                                            'Sin fecha'}
                                                    </span>
                                                </div>
                                                <Badge
                                                    className={
                                                        de.status ===
                                                            'completed'
                                                            ? 'bg-green-100 text-green-800'
                                                            : de.status ===
                                                                'in_progress'
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : de.status ===
                                                                    'assigned'
                                                                    ? 'bg-blue-100 text-blue-800'
                                                                    : 'bg-gray-100 text-gray-800'
                                                    }
                                                >
                                                    {de.status}
                                                </Badge>
                                            </Link>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No hay entregas programadas
                                    </p>
                                )}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Confirmation Dialog */}
            <AlertDialog
                open={confirmDialog.open}
                onOpenChange={(open) =>
                    setConfirmDialog({ ...confirmDialog, open })
                }
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {confirmDialog.title}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {confirmDialog.description}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={executeAction}
                            className={
                                confirmDialog.variant === 'destructive'
                                    ? 'bg-red-600 hover:bg-red-700'
                                    : ''
                            }
                        >
                            Confirmar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
