import { Alert, AlertDescription } from '@/components/ui/alert';
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
import { Head, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    Building2,
    CheckCircle,
    Coins,
    Edit2,
    Plus,
    Save,
    Settings,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    normal_balance: string;
}

interface TaxMapping {
    id: number;
    code: string;
    name: string;
    description: string | null;
    rate: number;
    sales_account_id: number;
    purchase_account_id: number | null;
    sales_account: Account | null;
    purchase_account: Account | null;
    is_active: boolean;
    is_default: boolean;
}

interface Settings {
    id: number;
    ar_account_id: number | null;
    ap_account_id: number | null;
    revenue_account_id: number | null;
    cogs_account_id: number | null;
    discount_account_id: number | null;
    inventory_account_id: number | null;
    cash_account_id: number | null;
    bank_account_id: number | null;
    exchange_gain_account_id: number | null;
    exchange_loss_account_id: number | null;
    isr_retention_account_id: number | null;
    itbis_retention_account_id: number | null;
    ar_account: Account | null;
    ap_account: Account | null;
    revenue_account: Account | null;
    cogs_account: Account | null;
    discount_account: Account | null;
    inventory_account: Account | null;
    cash_account: Account | null;
    bank_account: Account | null;
    exchange_gain_account: Account | null;
    exchange_loss_account: Account | null;
    isr_retention_account: Account | null;
    itbis_retention_account: Account | null;
}

interface ConfigurationStatus {
    is_configured: boolean;
    missing_required: string[];
}

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface Props {
    settings: Settings;
    accounts: Account[];
    taxMappings: TaxMapping[];
    baseCurrency: Currency | null;
    configurationStatus: ConfigurationStatus;
}

interface PageProps {
    flash?: { success?: string; error?: string };
    errors?: Record<string, string>;
    [key: string]: unknown;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Configuración', href: '/accounting/settings' },
];

const accountFields = [
    {
        key: 'ar_account_id',
        label: 'Cuentas por Cobrar',
        description: 'Cuenta para registrar facturas por cobrar',
        type: 'asset',
    },
    {
        key: 'ap_account_id',
        label: 'Cuentas por Pagar',
        description: 'Cuenta para registrar facturas por pagar',
        type: 'liability',
    },
    {
        key: 'revenue_account_id',
        label: 'Ingresos por Ventas',
        description: 'Cuenta principal de ingresos',
        type: 'revenue',
    },
    {
        key: 'cogs_account_id',
        label: 'Costo de Ventas',
        description: 'Costo de mercancía vendida',
        type: 'expense',
    },
    {
        key: 'discount_account_id',
        label: 'Descuentos en Ventas',
        description: 'Descuentos otorgados a clientes',
        type: 'revenue',
    },
    {
        key: 'inventory_account_id',
        label: 'Inventario',
        description: 'Cuenta de inventario de mercancías',
        type: 'asset',
    },
    {
        key: 'cash_account_id',
        label: 'Caja',
        description: 'Efectivo en caja',
        type: 'asset',
    },
    {
        key: 'bank_account_id',
        label: 'Banco Principal',
        description: 'Cuenta bancaria principal',
        type: 'asset',
    },
    {
        key: 'exchange_gain_account_id',
        label: 'Ganancia Cambiaria',
        description: 'Ganancias por diferencia de cambio',
        type: 'revenue',
    },
    {
        key: 'exchange_loss_account_id',
        label: 'Pérdida Cambiaria',
        description: 'Pérdidas por diferencia de cambio',
        type: 'expense',
    },
    {
        key: 'isr_retention_account_id',
        label: 'Retención ISR',
        description: 'Retención de impuesto sobre la renta',
        type: 'liability',
    },
    {
        key: 'itbis_retention_account_id',
        label: 'Retención ITBIS',
        description: 'Retención de ITBIS',
        type: 'liability',
    },
];

export default function AccountingSettingsIndex({
    settings,
    accounts,
    taxMappings,
    baseCurrency,
    configurationStatus,
}: Props) {
    const { flash, errors } = usePage<PageProps>().props;
    const [formData, setFormData] = useState<Record<string, string | null>>(
        () => {
            const data: Record<string, string | null> = {};
            accountFields.forEach((field) => {
                const value = settings[field.key as keyof Settings];
                data[field.key] = value ? String(value) : null;
            });
            return data;
        },
    );
    const [saving, setSaving] = useState(false);
    const [taxDialogOpen, setTaxDialogOpen] = useState(false);
    const [editingTax, setEditingTax] = useState<TaxMapping | null>(null);
    const [taxForm, setTaxForm] = useState({
        code: '',
        name: '',
        description: '',
        rate: '18',
        sales_account_id: '',
        purchase_account_id: '',
        is_active: true,
        is_default: false,
    });

    const handleAccountChange = (field: string, value: string) => {
        setFormData((prev) => ({
            ...prev,
            [field]: value === 'none' ? null : value,
        }));
    };

    const handleSaveSettings = () => {
        setSaving(true);
        router.put('/accounting/settings', formData, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const openTaxDialog = (tax?: TaxMapping) => {
        if (tax) {
            setEditingTax(tax);
            setTaxForm({
                code: tax.code,
                name: tax.name,
                description: tax.description || '',
                rate: String(tax.rate),
                sales_account_id: String(tax.sales_account_id),
                purchase_account_id: tax.purchase_account_id
                    ? String(tax.purchase_account_id)
                    : '',
                is_active: tax.is_active,
                is_default: tax.is_default,
            });
        } else {
            setEditingTax(null);
            setTaxForm({
                code: '',
                name: '',
                description: '',
                rate: '18',
                sales_account_id: '',
                purchase_account_id: '',
                is_active: true,
                is_default: false,
            });
        }
        setTaxDialogOpen(true);
    };

    const handleSaveTax = () => {
        const data = {
            ...taxForm,
            rate: parseFloat(taxForm.rate),
            sales_account_id: parseInt(taxForm.sales_account_id),
            purchase_account_id: taxForm.purchase_account_id
                ? parseInt(taxForm.purchase_account_id)
                : null,
        };

        if (editingTax) {
            router.put(`/accounting/tax-mappings/${editingTax.id}`, data, {
                preserveScroll: true,
                onSuccess: () => setTaxDialogOpen(false),
            });
        } else {
            router.post('/accounting/tax-mappings', data, {
                preserveScroll: true,
                onSuccess: () => setTaxDialogOpen(false),
            });
        }
    };

    const handleDeleteTax = (id: number) => {
        if (confirm('¿Está seguro de eliminar este mapeo de impuesto?')) {
            router.delete(`/accounting/tax-mappings/${id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración Contable" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">
                            Configuración Contable
                        </h1>
                        <p className="text-muted-foreground">
                            Configure las cuentas por defecto y mapeos de
                            impuestos
                        </p>
                    </div>
                    <Button onClick={handleSaveSettings} disabled={saving}>
                        <Save className="mr-2 h-4 w-4" />
                        {saving ? 'Guardando...' : 'Guardar Configuración'}
                    </Button>
                </div>

                {/* Flash Messages */}
                {flash?.success && (
                    <Alert className="border-emerald-500/50 bg-emerald-500/10">
                        <CheckCircle className="h-4 w-4 text-emerald-500" />
                        <AlertDescription className="text-emerald-300">
                            {flash.success}
                        </AlertDescription>
                    </Alert>
                )}

                {errors?.general && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{errors.general}</AlertDescription>
                    </Alert>
                )}

                {/* Configuration Status */}
                {!configurationStatus.is_configured && (
                    <Alert className="border-amber-500/50 bg-amber-500/10">
                        <AlertCircle className="h-4 w-4 text-amber-500" />
                        <AlertDescription className="text-amber-300">
                            Configuración incompleta. Faltan las siguientes
                            cuentas requeridas:{' '}
                            {configurationStatus.missing_required.join(', ')}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Base Currency Info */}
                <Card className="border-border/50 bg-card/50">
                    <CardHeader className="flex flex-row items-center gap-4 space-y-0">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/20">
                            <Coins className="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <CardTitle className="text-base">
                                Moneda Base
                            </CardTitle>
                            <CardDescription>
                                La moneda base se configura desde Configuración
                                &gt; Monedas
                            </CardDescription>
                        </div>
                        {baseCurrency && (
                            <Badge variant="outline" className="ml-auto">
                                {baseCurrency.code} - {baseCurrency.name}
                            </Badge>
                        )}
                    </CardHeader>
                </Card>

                {/* Default Accounts */}
                <Card className="border-border/50 bg-card/50">
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Building2 className="h-5 w-5 text-primary" />
                            <CardTitle>Cuentas por Defecto</CardTitle>
                        </div>
                        <CardDescription>
                            Estas cuentas se utilizan para el asiento automático
                            de facturas y pagos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-6 md:grid-cols-2">
                            {accountFields.map((field) => (
                                <div key={field.key} className="space-y-2">
                                    <Label htmlFor={field.key}>
                                        {field.label}
                                    </Label>
                                    <Select
                                        value={formData[field.key] || 'none'}
                                        onValueChange={(value) =>
                                            handleAccountChange(
                                                field.key,
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="border-border/50 bg-background/50">
                                            <SelectValue placeholder="Seleccionar cuenta..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">
                                                <span className="text-muted-foreground">
                                                    Sin seleccionar
                                                </span>
                                            </SelectItem>
                                            {accounts.map((account) => (
                                                <SelectItem
                                                    key={account.id}
                                                    value={String(account.id)}
                                                >
                                                    <span className="font-mono text-muted-foreground">
                                                        {account.code}
                                                    </span>{' '}
                                                    - {account.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-xs text-muted-foreground">
                                        {field.description}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Tax Mappings */}
                <Card className="border-border/50 bg-card/50">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div className="flex items-center gap-2">
                            <Settings className="h-5 w-5 text-primary" />
                            <div>
                                <CardTitle>Mapeo de Impuestos</CardTitle>
                                <CardDescription>
                                    Configure los impuestos y sus cuentas
                                    contables asociadas
                                </CardDescription>
                            </div>
                        </div>
                        <Button onClick={() => openTaxDialog()}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo Impuesto
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {taxMappings.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-8 text-muted-foreground">
                                <Settings className="mb-2 h-8 w-8 opacity-50" />
                                <p>No hay impuestos configurados</p>
                            </div>
                        ) : (
                            <div className="rounded-md border border-border/50">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-muted/30">
                                            <TableHead>Código</TableHead>
                                            <TableHead>Nombre</TableHead>
                                            <TableHead className="text-right">
                                                Tasa %
                                            </TableHead>
                                            <TableHead>Cuenta Ventas</TableHead>
                                            <TableHead>
                                                Cuenta Compras
                                            </TableHead>
                                            <TableHead className="text-center">
                                                Estado
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Acciones
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {taxMappings.map((tax) => (
                                            <TableRow key={tax.id}>
                                                <TableCell className="font-mono font-medium">
                                                    {tax.code}
                                                    {tax.is_default && (
                                                        <Badge
                                                            variant="outline"
                                                            className="ml-2 text-xs"
                                                        >
                                                            Default
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {tax.name}
                                                </TableCell>
                                                <TableCell className="text-right font-mono">
                                                    {tax.rate}%
                                                </TableCell>
                                                <TableCell>
                                                    {tax.sales_account && (
                                                        <span className="text-sm">
                                                            <span className="font-mono text-muted-foreground">
                                                                {
                                                                    tax
                                                                        .sales_account
                                                                        .code
                                                                }
                                                            </span>{' '}
                                                            {
                                                                tax
                                                                    .sales_account
                                                                    .name
                                                            }
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {tax.purchase_account ? (
                                                        <span className="text-sm">
                                                            <span className="font-mono text-muted-foreground">
                                                                {
                                                                    tax
                                                                        .purchase_account
                                                                        .code
                                                                }
                                                            </span>{' '}
                                                            {
                                                                tax
                                                                    .purchase_account
                                                                    .name
                                                            }
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            -
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge
                                                        variant="outline"
                                                        className={
                                                            tax.is_active
                                                                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                                                                : 'border-red-500/30 bg-red-500/10 text-red-300'
                                                        }
                                                    >
                                                        {tax.is_active
                                                            ? 'Activo'
                                                            : 'Inactivo'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                openTaxDialog(
                                                                    tax,
                                                                )
                                                            }
                                                        >
                                                            <Edit2 className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                handleDeleteTax(
                                                                    tax.id,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4 text-red-400" />
                                                        </Button>
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
            </div>

            {/* Tax Dialog */}
            <Dialog open={taxDialogOpen} onOpenChange={setTaxDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {editingTax ? 'Editar Impuesto' : 'Nuevo Impuesto'}
                        </DialogTitle>
                        <DialogDescription>
                            Configure el impuesto y sus cuentas contables
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label>Código</Label>
                                <Input
                                    value={taxForm.code}
                                    onChange={(e) =>
                                        setTaxForm({
                                            ...taxForm,
                                            code: e.target.value.toUpperCase(),
                                        })
                                    }
                                    placeholder="ITBIS"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Tasa (%)</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={taxForm.rate}
                                    onChange={(e) =>
                                        setTaxForm({
                                            ...taxForm,
                                            rate: e.target.value,
                                        })
                                    }
                                    placeholder="18"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>Nombre</Label>
                            <Input
                                value={taxForm.name}
                                onChange={(e) =>
                                    setTaxForm({
                                        ...taxForm,
                                        name: e.target.value,
                                    })
                                }
                                placeholder="Impuesto a la Transferencia de Bienes"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Cuenta Ventas (Impuesto por Pagar)</Label>
                            <Select
                                value={taxForm.sales_account_id}
                                onValueChange={(value) =>
                                    setTaxForm({
                                        ...taxForm,
                                        sales_account_id: value,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar cuenta..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {accounts.map((account) => (
                                        <SelectItem
                                            key={account.id}
                                            value={String(account.id)}
                                        >
                                            <span className="font-mono text-muted-foreground">
                                                {account.code}
                                            </span>{' '}
                                            - {account.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>Cuenta Compras (Impuesto Pagado)</Label>
                            <Select
                                value={taxForm.purchase_account_id || 'none'}
                                onValueChange={(value) =>
                                    setTaxForm({
                                        ...taxForm,
                                        purchase_account_id:
                                            value === 'none' ? '' : value,
                                    })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar cuenta..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Ninguna
                                    </SelectItem>
                                    {accounts.map((account) => (
                                        <SelectItem
                                            key={account.id}
                                            value={String(account.id)}
                                        >
                                            <span className="font-mono text-muted-foreground">
                                                {account.code}
                                            </span>{' '}
                                            - {account.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Switch
                                    checked={taxForm.is_active}
                                    onCheckedChange={(checked) =>
                                        setTaxForm({
                                            ...taxForm,
                                            is_active: checked,
                                        })
                                    }
                                />
                                <Label>Activo</Label>
                            </div>
                            <div className="flex items-center gap-2">
                                <Switch
                                    checked={taxForm.is_default}
                                    onCheckedChange={(checked) =>
                                        setTaxForm({
                                            ...taxForm,
                                            is_default: checked,
                                        })
                                    }
                                />
                                <Label>Impuesto por Defecto</Label>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setTaxDialogOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button onClick={handleSaveTax}>Guardar</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
