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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { router } from '@inertiajs/react';
import { DollarSign, Edit, Package, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

export interface Charge {
    id: number;
    code: string;
    description: string;
    charge_type: string;
    basis: string;
    currency_code: string;
    unit_price: number;
    qty: number;
    amount: number;
    is_tax_included: boolean;
    is_manual: boolean;
    sort_order: number | null;
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

interface ChargesSectionProps {
    orderId: number;
    charges: Charge[];
    currencies: Currency[];
    chargeTypes: OptionItem[];
    chargeBases: OptionItem[];
    productsServices: ProductService[];
    orderCurrencyCode: string;
    canManage: boolean;
}

const chargeTypeLabels: Record<string, string> = {
    freight: 'Flete',
    surcharge: 'Recargo',
    tax: 'Impuesto',
    other: 'Otro',
};

const basisLabels: Record<string, string> = {
    flat: 'Fijo',
    per_kg: 'Por Kg',
    per_cbm: 'Por CBM',
    per_shipment: 'Por Envío',
};

// Map product type to charge type
const productTypeToChargeType: Record<string, string> = {
    service: 'freight',
    product: 'other',
    fee: 'surcharge',
};

// Empty form state
const getEmptyForm = (currencyCode: string) => ({
    code: '',
    description: '',
    charge_type: 'freight',
    basis: 'flat',
    currency_code: currencyCode,
    unit_price: '',
    qty: '1',
    is_tax_included: false,
});

// Add/Edit Charge Dialog
function ChargeFormDialog({
    orderId,
    currencies,
    chargeTypes,
    chargeBases,
    productsServices,
    orderCurrencyCode,
    charge,
    onClose,
}: {
    orderId: number;
    currencies: Currency[];
    chargeTypes: OptionItem[];
    chargeBases: OptionItem[];
    productsServices: ProductService[];
    orderCurrencyCode: string;
    charge?: Charge | null;
    onClose: () => void;
}) {
    const isEditing = !!charge;
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isManualMode, setIsManualMode] = useState(isEditing);
    const [selectedProductId, setSelectedProductId] = useState<string>('');
    const [form, setForm] = useState(
        isEditing
            ? {
                  code: charge.code,
                  description: charge.description,
                  charge_type: charge.charge_type,
                  basis: charge.basis,
                  currency_code: charge.currency_code,
                  unit_price: String(charge.unit_price),
                  qty: String(charge.qty),
                  is_tax_included: charge.is_tax_included,
              }
            : getEmptyForm(orderCurrencyCode),
    );

    // Handle product selection
    const handleProductSelect = (productId: string) => {
        setSelectedProductId(productId);

        if (productId === 'manual') {
            setIsManualMode(true);
            setForm(getEmptyForm(orderCurrencyCode));
            return;
        }

        const product = productsServices.find(
            (p) => String(p.id) === productId,
        );
        if (product) {
            setIsManualMode(false);
            setForm({
                code: product.code,
                description: product.description || product.name,
                charge_type: productTypeToChargeType[product.type] || 'other',
                basis: 'flat',
                currency_code: orderCurrencyCode, // Always use order currency
                unit_price: product.default_unit_price
                    ? String(product.default_unit_price)
                    : '',
                qty: '1',
                is_tax_included: false,
            });
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const data = {
            ...form,
            unit_price: parseFloat(form.unit_price) || 0,
            qty: parseFloat(form.qty) || 1,
        };

        if (isEditing) {
            router.put(
                `/shipping-orders/${orderId}/charges/${charge.id}`,
                data,
                {
                    preserveScroll: true,
                    onSuccess: () => onClose(),
                    onFinish: () => setIsSubmitting(false),
                },
            );
        } else {
            router.post(`/shipping-orders/${orderId}/charges`, data, {
                preserveScroll: true,
                onSuccess: () => onClose(),
                onFinish: () => setIsSubmitting(false),
            });
        }
    };

    // Calculate amount preview
    const amountPreview =
        (parseFloat(form.unit_price) || 0) * (parseFloat(form.qty) || 1);
    const currencySymbol =
        currencies.find((c) => c.code === form.currency_code)?.symbol || '$';

    return (
        <DialogContent className="sm:max-w-lg">
            <form onSubmit={handleSubmit}>
                <DialogHeader>
                    <DialogTitle>
                        {isEditing ? 'Editar Cargo' : 'Agregar Cargo'}
                    </DialogTitle>
                    <DialogDescription>
                        {isEditing
                            ? 'Modifica los detalles del cargo'
                            : 'Selecciona un producto/servicio o crea un cargo manual'}
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    {/* Product/Service Selector - Only show when creating */}
                    {!isEditing && (
                        <div className="space-y-2">
                            <Label>Producto / Servicio</Label>
                            <Select
                                value={selectedProductId}
                                onValueChange={handleProductSelect}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar del catálogo..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="manual">
                                        <div className="flex items-center gap-2">
                                            <Pencil className="h-4 w-4" />
                                            <span>Cargo Manual</span>
                                        </div>
                                    </SelectItem>
                                    {productsServices.length > 0 && (
                                        <div className="my-1 border-t" />
                                    )}
                                    {productsServices.map((product) => (
                                        <SelectItem
                                            key={product.id}
                                            value={String(product.id)}
                                        >
                                            <div className="flex items-center gap-2">
                                                <Package className="h-4 w-4 text-muted-foreground" />
                                                <span className="font-mono text-xs">
                                                    {product.code}
                                                </span>
                                                <span className="truncate">
                                                    - {product.name}
                                                </span>
                                                {product.default_unit_price && (
                                                    <span className="ml-auto text-xs text-muted-foreground">
                                                        {product
                                                            .default_currency
                                                            ?.symbol || '$'}
                                                        {Number(
                                                            product.default_unit_price,
                                                        ).toFixed(2)}
                                                    </span>
                                                )}
                                            </div>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {/* Show form fields when product selected or editing */}
                    {(selectedProductId || isEditing) && (
                        <>
                            {/* Code and Type */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="code">Código *</Label>
                                    <Input
                                        id="code"
                                        placeholder="FRT001"
                                        value={form.code}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                code: e.target.value,
                                            })
                                        }
                                        disabled={!isManualMode && !isEditing}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="charge_type">Tipo *</Label>
                                    <Select
                                        value={form.charge_type}
                                        onValueChange={(value) =>
                                            setForm({
                                                ...form,
                                                charge_type: value,
                                            })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {chargeTypes.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">
                                    Descripción *
                                </Label>
                                <Input
                                    id="description"
                                    placeholder="Flete marítimo Shanghai - Santo Domingo"
                                    value={form.description}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            description: e.target.value,
                                        })
                                    }
                                    required
                                />
                            </div>

                            {/* Basis and Currency */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="basis">Base *</Label>
                                    <Select
                                        value={form.basis}
                                        onValueChange={(value) =>
                                            setForm({ ...form, basis: value })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {chargeBases.map((basis) => (
                                                <SelectItem
                                                    key={basis.value}
                                                    value={basis.value}
                                                >
                                                    {basis.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Moneda</Label>
                                    <div className="flex h-10 items-center rounded-md border bg-muted/50 px-3 text-sm">
                                        <span className="font-medium">
                                            {orderCurrencyCode}
                                        </span>
                                        <span className="ml-2 text-muted-foreground">
                                            (Moneda de la orden)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Quantity and Unit Price */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="qty">Cantidad *</Label>
                                    <Input
                                        id="qty"
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        value={form.qty}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                qty: e.target.value,
                                            })
                                        }
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="unit_price">
                                        Precio Unitario *
                                    </Label>
                                    <Input
                                        id="unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={form.unit_price}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                unit_price: e.target.value,
                                            })
                                        }
                                        required
                                    />
                                </div>
                            </div>

                            {/* Amount Preview */}
                            <div className="flex items-center justify-between rounded-lg border bg-muted/30 p-3">
                                <span className="text-sm text-muted-foreground">
                                    Monto Total:
                                </span>
                                <span className="text-lg font-semibold">
                                    {currencySymbol}{' '}
                                    {amountPreview.toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 4,
                                    })}
                                </span>
                            </div>
                        </>
                    )}
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        disabled={isSubmitting}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        disabled={
                            !form.code ||
                            !form.description ||
                            !form.unit_price ||
                            isSubmitting
                        }
                    >
                        {isSubmitting
                            ? 'Guardando...'
                            : isEditing
                              ? 'Actualizar'
                              : 'Agregar'}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    );
}

export default function ChargesSection({
    orderId,
    charges,
    currencies,
    chargeTypes,
    chargeBases,
    productsServices,
    orderCurrencyCode,
    canManage,
}: ChargesSectionProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingCharge, setEditingCharge] = useState<Charge | null>(null);
    const [deleteConfirm, setDeleteConfirm] = useState<{
        open: boolean;
        charge: Charge | null;
    }>({ open: false, charge: null });
    const [isDeleting, setIsDeleting] = useState(false);

    // Calculate totals by currency
    const totalsByCurrency = charges.reduce(
        (acc, charge) => {
            if (!acc[charge.currency_code]) {
                acc[charge.currency_code] = 0;
            }
            acc[charge.currency_code] += Number(charge.amount);
            return acc;
        },
        {} as Record<string, number>,
    );

    const handleEdit = (charge: Charge) => {
        setEditingCharge(charge);
        setDialogOpen(true);
    };

    const handleCloseDialog = () => {
        setDialogOpen(false);
        setEditingCharge(null);
    };

    const handleDelete = (charge: Charge) => {
        setDeleteConfirm({ open: true, charge });
    };

    const confirmDelete = () => {
        if (!deleteConfirm.charge) return;
        setIsDeleting(true);

        router.delete(
            `/shipping-orders/${orderId}/charges/${deleteConfirm.charge.id}`,
            {
                preserveScroll: true,
                onFinish: () => {
                    setIsDeleting(false);
                    setDeleteConfirm({ open: false, charge: null });
                },
            },
        );
    };

    const getCurrencySymbol = (code: string) => {
        return currencies.find((c) => c.code === code)?.symbol || code;
    };

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <DollarSign className="h-5 w-5" />
                        Cargos
                    </CardTitle>
                    <CardDescription>
                        {charges.length === 0
                            ? 'No hay cargos registrados'
                            : `${charges.length} cargo${charges.length !== 1 ? 's' : ''} registrado${charges.length !== 1 ? 's' : ''}`}
                    </CardDescription>
                </div>
                {canManage && (
                    <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                        <DialogTrigger asChild>
                            <Button
                                size="sm"
                                onClick={() => {
                                    setEditingCharge(null);
                                    setDialogOpen(true);
                                }}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Agregar
                            </Button>
                        </DialogTrigger>
                        {dialogOpen && (
                            <ChargeFormDialog
                                orderId={orderId}
                                currencies={currencies}
                                chargeTypes={chargeTypes}
                                chargeBases={chargeBases}
                                productsServices={productsServices}
                                orderCurrencyCode={orderCurrencyCode}
                                charge={editingCharge}
                                onClose={handleCloseDialog}
                            />
                        )}
                    </Dialog>
                )}
            </CardHeader>
            <CardContent>
                {charges.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-10 text-center">
                        <DollarSign className="mb-3 h-10 w-10 text-muted-foreground/50" />
                        <p className="text-muted-foreground">
                            No hay cargos asociados a esta orden.
                        </p>
                        {canManage && (
                            <p className="mt-1 text-sm text-muted-foreground/70">
                                Agrega cargos para poder generar una
                                pre-factura.
                            </p>
                        )}
                    </div>
                ) : (
                    <div className="space-y-4">
                        {/* Charges Table */}
                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="w-[100px]">
                                            Código
                                        </TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="w-[90px]">
                                            Tipo
                                        </TableHead>
                                        <TableHead className="w-[80px] text-right">
                                            Cant.
                                        </TableHead>
                                        <TableHead className="w-[110px] text-right">
                                            P. Unit.
                                        </TableHead>
                                        <TableHead className="w-[110px] text-right">
                                            Monto
                                        </TableHead>
                                        {canManage && (
                                            <TableHead className="w-[80px]" />
                                        )}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {charges.map((charge) => (
                                        <TableRow key={charge.id}>
                                            <TableCell className="font-mono text-sm">
                                                {charge.code}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <span className="truncate">
                                                        {charge.description}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className="text-xs"
                                                >
                                                    {chargeTypeLabels[
                                                        charge.charge_type
                                                    ] || charge.charge_type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {Number(charge.qty)}
                                                <span className="ml-1 text-xs text-muted-foreground">
                                                    {basisLabels[
                                                        charge.basis
                                                    ] !== 'Fijo'
                                                        ? basisLabels[
                                                              charge.basis
                                                          ]
                                                        : ''}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <span className="text-muted-foreground">
                                                    {getCurrencySymbol(
                                                        charge.currency_code,
                                                    )}
                                                </span>{' '}
                                                {Number(
                                                    charge.unit_price,
                                                ).toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                <span className="text-muted-foreground">
                                                    {getCurrencySymbol(
                                                        charge.currency_code,
                                                    )}
                                                </span>{' '}
                                                {Number(
                                                    charge.amount,
                                                ).toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </TableCell>
                                            {canManage && (
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8"
                                                            onClick={() =>
                                                                handleEdit(
                                                                    charge,
                                                                )
                                                            }
                                                            title="Editar"
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-8 w-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                            onClick={() =>
                                                                handleDelete(
                                                                    charge,
                                                                )
                                                            }
                                                            title="Eliminar"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Totals */}
                        <div className="flex justify-end">
                            <div className="min-w-[200px] space-y-1 rounded-lg border bg-muted/30 p-3">
                                {Object.entries(totalsByCurrency).map(
                                    ([currency, total]) => (
                                        <div
                                            key={currency}
                                            className="flex items-center justify-between"
                                        >
                                            <span className="text-sm text-muted-foreground">
                                                Total ({currency}):
                                            </span>
                                            <span className="font-semibold">
                                                {getCurrencySymbol(currency)}{' '}
                                                {total.toLocaleString('en-US', {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </span>
                                        </div>
                                    ),
                                )}
                            </div>
                        </div>
                    </div>
                )}
            </CardContent>

            {/* Delete Confirmation Dialog */}
            <AlertDialog
                open={deleteConfirm.open}
                onOpenChange={(open) =>
                    setDeleteConfirm({ ...deleteConfirm, open })
                }
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar cargo?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Esta acción eliminará el cargo "
                            {deleteConfirm.charge?.code}" permanentemente.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel disabled={isDeleting}>
                            Cancelar
                        </AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmDelete}
                            disabled={isDeleting}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            {isDeleting ? 'Eliminando...' : 'Eliminar'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </Card>
    );
}
