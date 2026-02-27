import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
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
import { useForm } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    normal_balance: string;
    parent_id: number | null;
    is_postable: boolean;
    requires_subsidiary: boolean;
    is_active: boolean;
    is_bank_account?: boolean;
    bank_name?: string;
    bank_account_number?: string;
    description?: string;
}

interface Props {
    open: boolean;
    onClose: () => void;
    account?: Account | null;
    allAccounts: Account[];
    accountTypes: Array<{ value: string; label: string }>;
}

export default function AccountFormDrawer({
    open,
    onClose,
    account,
    allAccounts,
    accountTypes,
}: Props) {
    const isEdit = !!account;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        code: account?.code || '',
        name: account?.name || '',
        type: account?.type || 'asset',
        normal_balance: account?.normal_balance || 'debit',
        parent_id: account?.parent_id || '',
        is_postable: account?.is_postable ?? true,
        requires_subsidiary: account?.requires_subsidiary ?? false,
        is_active: account?.is_active ?? true,
        is_bank_account: account?.is_bank_account ?? false,
        bank_name: account?.bank_name || '',
        bank_account_number: account?.bank_account_number || '',
        description: account?.description || '',
    });

    // Auto-set normal_balance based on type
    const handleTypeChange = (type: string) => {
        setData((prev) => ({
            ...prev,
            type,
            normal_balance:
                type === 'asset' || type === 'expense' ? 'debit' : 'credit',
        }));
    };

    // Filter only non-postable accounts for parent (headers only)
    const parentOptions = allAccounts.filter(
        (acc) => !acc.is_postable && (!isEdit || acc.id !== account?.id), // Cannot be its own parent
    );

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        const submitData = {
            ...data,
            parent_id: data.parent_id || null,
        };

        if (isEdit) {
            put(`/accounting/accounts/${account.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        } else {
            post('/accounting/accounts', {
                preserveScroll: true,
                onSuccess: () => {
                    reset();
                    onClose();
                },
            });
        }
    };

    return (
        <Drawer open={open} onOpenChange={onClose}>
            <DrawerContent className="max-h-[90vh] px-6">
                <DrawerHeader className="px-0">
                    <DrawerTitle className="text-2xl">
                        {isEdit ? 'Editar Cuenta' : 'Nueva Cuenta'}
                    </DrawerTitle>
                    <DrawerDescription>
                        {isEdit
                            ? 'Modifica los datos de la cuenta contable'
                            : 'Crea una nueva cuenta en el plan de cuentas'}
                    </DrawerDescription>
                </DrawerHeader>

                <form
                    onSubmit={handleSubmit}
                    className="mt-4 space-y-5 overflow-y-auto pb-6"
                >
                    {/* Code */}
                    <div>
                        <Label htmlFor="code">
                            Código <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="code"
                            value={data.code}
                            onChange={(e) =>
                                setData('code', e.target.value.toUpperCase())
                            }
                            placeholder="1000, 1100, 1110..."
                            className={errors.code ? 'border-red-500' : ''}
                            required
                        />
                        {errors.code && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.code}
                            </p>
                        )}
                        <p className="mt-1 text-xs text-muted-foreground">
                            Solo mayúsculas, números y guiones
                        </p>
                    </div>

                    {/* Name */}
                    <div>
                        <Label htmlFor="name">
                            Nombre <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Efectivo y Bancos"
                            className={errors.name ? 'border-red-500' : ''}
                            required
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.name}
                            </p>
                        )}
                    </div>

                    {/* Type */}
                    <div>
                        <Label htmlFor="type">
                            Tipo <span className="text-red-500">*</span>
                        </Label>
                        <Select
                            value={data.type}
                            onValueChange={handleTypeChange}
                        >
                            <SelectTrigger
                                className={errors.type ? 'border-red-500' : ''}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {accountTypes.map((type) => (
                                    <SelectItem
                                        key={type.value}
                                        value={type.value}
                                    >
                                        {type.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.type && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.type}
                            </p>
                        )}
                    </div>

                    {/* Normal Balance */}
                    <div>
                        <Label htmlFor="normal_balance">
                            Balance Normal{' '}
                            <span className="text-red-500">*</span>
                        </Label>
                        <Select
                            value={data.normal_balance}
                            onValueChange={(value) =>
                                setData('normal_balance', value)
                            }
                        >
                            <SelectTrigger
                                className={
                                    errors.normal_balance
                                        ? 'border-red-500'
                                        : ''
                                }
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="debit">
                                    Débito (D)
                                </SelectItem>
                                <SelectItem value="credit">
                                    Crédito (C)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.normal_balance && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.normal_balance}
                            </p>
                        )}
                    </div>

                    {/* Parent Account */}
                    <div>
                        <Label htmlFor="parent_id">Cuenta Padre</Label>
                        <Select
                            value={data.parent_id?.toString() || 'none'}
                            onValueChange={(value) =>
                                setData(
                                    'parent_id',
                                    value === 'none' ? '' : parseInt(value),
                                )
                            }
                        >
                            <SelectTrigger
                                className={
                                    errors.parent_id ? 'border-red-500' : ''
                                }
                            >
                                <SelectValue placeholder="Sin padre (cuenta raíz)" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">
                                    Sin padre (cuenta raíz)
                                </SelectItem>
                                {parentOptions.map((acc) => (
                                    <SelectItem
                                        key={acc.id}
                                        value={acc.id.toString()}
                                    >
                                        {acc.code} - {acc.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.parent_id && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.parent_id}
                            </p>
                        )}
                        <p className="mt-1 text-xs text-muted-foreground">
                            Solo cuentas de agrupación (no posteables)
                        </p>
                    </div>

                    {/* Is Postable */}
                    <div className="flex items-start space-x-2">
                        <Checkbox
                            id="is_postable"
                            checked={data.is_postable}
                            onCheckedChange={(checked) =>
                                setData('is_postable', !!checked)
                            }
                        />
                        <div className="grid gap-1.5 leading-none">
                            <Label
                                htmlFor="is_postable"
                                className="cursor-pointer font-medium"
                            >
                                Cuenta Posteable
                            </Label>
                            <p className="text-xs text-muted-foreground">
                                Permite asientos contables (desmarcar para
                                cuentas de agrupación)
                            </p>
                        </div>
                    </div>

                    {errors.is_postable && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                {errors.is_postable}
                            </AlertDescription>
                        </Alert>
                    )}

                    {/* Requires Subsidiary */}
                    <div className="flex items-start space-x-2">
                        <Checkbox
                            id="requires_subsidiary"
                            checked={data.requires_subsidiary}
                            onCheckedChange={(checked) =>
                                setData('requires_subsidiary', !!checked)
                            }
                        />
                        <div className="grid gap-1.5 leading-none">
                            <Label
                                htmlFor="requires_subsidiary"
                                className="cursor-pointer font-medium"
                            >
                                Requiere Auxiliar
                            </Label>
                            <p className="text-xs text-muted-foreground">
                                Requiere cliente/proveedor (ej: Cuentas por
                                Cobrar)
                            </p>
                        </div>
                    </div>

                    {/* Is Active */}
                    <div className="flex items-start space-x-2">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) =>
                                setData('is_active', !!checked)
                            }
                        />
                        <div className="grid gap-1.5 leading-none">
                            <Label
                                htmlFor="is_active"
                                className="cursor-pointer font-medium"
                            >
                                Activa
                            </Label>
                            <p className="text-xs text-muted-foreground">
                                Visible en selección de cuentas
                            </p>
                        </div>
                    </div>

                    {/* Bank Account Section - only for asset type */}
                    {data.type === 'asset' && (
                        <div className="space-y-4 rounded-lg border border-blue-500/30 bg-blue-500/5 p-4">
                            <div className="flex items-start space-x-2">
                                <Checkbox
                                    id="is_bank_account"
                                    checked={data.is_bank_account}
                                    onCheckedChange={(checked) =>
                                        setData('is_bank_account', !!checked)
                                    }
                                />
                                <div className="grid gap-1.5 leading-none">
                                    <Label
                                        htmlFor="is_bank_account"
                                        className="cursor-pointer font-medium text-blue-300"
                                    >
                                        🏦 Es Cuenta Bancaria
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        Habilitar para cuentas de banco o caja
                                        que participan en conciliación
                                    </p>
                                </div>
                            </div>

                            {data.is_bank_account && (
                                <>
                                    <div>
                                        <Label htmlFor="bank_name">
                                            Nombre del Banco
                                        </Label>
                                        <Input
                                            id="bank_name"
                                            value={data.bank_name}
                                            onChange={(e) =>
                                                setData(
                                                    'bank_name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Banco Popular, BHD León..."
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="bank_account_number">
                                            Número de Cuenta
                                        </Label>
                                        <Input
                                            id="bank_account_number"
                                            value={data.bank_account_number}
                                            onChange={(e) =>
                                                setData(
                                                    'bank_account_number',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="123-456789-0"
                                        />
                                    </div>
                                </>
                            )}
                        </div>
                    )}

                    {/* Description */}
                    <div>
                        <Label htmlFor="description">Descripción</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Descripción opcional de la cuenta..."
                            rows={3}
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-red-500">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    {/* Buttons */}
                    <div className="flex gap-3 pt-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="flex-1"
                        >
                            {processing
                                ? 'Guardando...'
                                : isEdit
                                  ? 'Actualizar'
                                  : 'Crear Cuenta'}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                reset();
                                onClose();
                            }}
                            disabled={processing}
                        >
                            Cancelar
                        </Button>
                    </div>
                </form>
            </DrawerContent>
        </Drawer>
    );
}
