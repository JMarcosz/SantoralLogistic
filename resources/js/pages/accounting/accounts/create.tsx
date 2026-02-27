import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, BookOpen, Save } from 'lucide-react';
import { FormEventHandler } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    is_postable: boolean;
}

interface Props {
    allAccounts: Account[];
    accountTypes: Array<{ value: string; label: string }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Plan de Cuentas', href: '/accounting/accounts' },
    { title: 'Nueva Cuenta', href: '/accounting/accounts/create' },
];

export default function CreateAccount({ allAccounts, accountTypes }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        name: '',
        type: 'asset',
        normal_balance: 'debit',
        parent_id: '' as string | number,
        is_postable: true,
        requires_subsidiary: false,
        is_active: true,
        is_bank_account: false,
        bank_name: '',
        bank_account_number: '',
        description: '',
    });

    // Auto-set normal_balance based on type
    const handleTypeChange = (type: string) => {
        setData((prev) => ({
            ...prev,
            type,
            normal_balance:
                type === 'asset' || type === 'expense' ? 'debit' : 'credit',
            // Reset bank account fields if not asset
            is_bank_account: type === 'asset' ? prev.is_bank_account : false,
        }));
    };

    // Filter only non-postable accounts for parent (headers only)
    const parentOptions = allAccounts.filter((acc) => !acc.is_postable);

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/accounting/accounts', {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Cuenta" />

            <div className="mx-auto max-w-2xl space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/accounting/accounts">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10">
                            <BookOpen className="h-5 w-5 text-emerald-400" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-white">
                                Nueva Cuenta
                            </h1>
                            <p className="text-sm text-slate-400">
                                Agregar cuenta al plan de cuentas
                            </p>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <Card className="border-white/10 bg-slate-800/50">
                        <CardHeader>
                            <CardTitle className="text-white">
                                Información de la Cuenta
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            {/* Code & Name Row */}
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <Label htmlFor="code">
                                        Código{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="code"
                                        value={data.code}
                                        onChange={(e) =>
                                            setData(
                                                'code',
                                                e.target.value.toUpperCase(),
                                            )
                                        }
                                        placeholder="1.1.01"
                                        className={
                                            errors.code ? 'border-red-500' : ''
                                        }
                                        required
                                    />
                                    {errors.code && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {errors.code}
                                        </p>
                                    )}
                                </div>
                                <div className="sm:col-span-2">
                                    <Label htmlFor="name">
                                        Nombre{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) =>
                                            setData('name', e.target.value)
                                        }
                                        placeholder="Caja General"
                                        className={
                                            errors.name ? 'border-red-500' : ''
                                        }
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {errors.name}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Type & Normal Balance Row */}
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="type">
                                        Tipo{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={data.type}
                                        onValueChange={handleTypeChange}
                                    >
                                        <SelectTrigger
                                            className={
                                                errors.type
                                                    ? 'border-red-500'
                                                    : ''
                                            }
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
                                                Débito
                                            </SelectItem>
                                            <SelectItem value="credit">
                                                Crédito
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.normal_balance && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {errors.normal_balance}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Parent Account */}
                            <div>
                                <Label htmlFor="parent_id">Cuenta Padre</Label>
                                <Select
                                    value={data.parent_id?.toString() || 'none'}
                                    onValueChange={(value) =>
                                        setData(
                                            'parent_id',
                                            value === 'none'
                                                ? ''
                                                : parseInt(value),
                                        )
                                    }
                                >
                                    <SelectTrigger>
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

                            {/* Checkboxes */}
                            <div className="grid gap-4 sm:grid-cols-3">
                                <div className="flex items-start space-x-2">
                                    <Checkbox
                                        id="is_postable"
                                        checked={data.is_postable}
                                        onCheckedChange={(checked) =>
                                            setData('is_postable', !!checked)
                                        }
                                    />
                                    <div className="grid gap-1 leading-none">
                                        <Label
                                            htmlFor="is_postable"
                                            className="cursor-pointer font-medium"
                                        >
                                            Posteable
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            Permite asientos
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start space-x-2">
                                    <Checkbox
                                        id="requires_subsidiary"
                                        checked={data.requires_subsidiary}
                                        onCheckedChange={(checked) =>
                                            setData(
                                                'requires_subsidiary',
                                                !!checked,
                                            )
                                        }
                                    />
                                    <div className="grid gap-1 leading-none">
                                        <Label
                                            htmlFor="requires_subsidiary"
                                            className="cursor-pointer font-medium"
                                        >
                                            Req. Auxiliar
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            Cliente/Proveedor
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start space-x-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onCheckedChange={(checked) =>
                                            setData('is_active', !!checked)
                                        }
                                    />
                                    <div className="grid gap-1 leading-none">
                                        <Label
                                            htmlFor="is_active"
                                            className="cursor-pointer font-medium"
                                        >
                                            Activa
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            Visible en sistema
                                        </p>
                                    </div>
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

                            {/* Bank Account Section */}
                            {data.type === 'asset' && (
                                <div className="space-y-4 rounded-lg border border-blue-500/30 bg-blue-500/5 p-4">
                                    <div className="flex items-start space-x-2">
                                        <Checkbox
                                            id="is_bank_account"
                                            checked={data.is_bank_account}
                                            onCheckedChange={(checked) =>
                                                setData(
                                                    'is_bank_account',
                                                    !!checked,
                                                )
                                            }
                                        />
                                        <div className="grid gap-1 leading-none">
                                            <Label
                                                htmlFor="is_bank_account"
                                                className="cursor-pointer font-medium text-blue-300"
                                            >
                                                🏦 Es Cuenta Bancaria
                                            </Label>
                                            <p className="text-xs text-muted-foreground">
                                                Para conciliación bancaria
                                            </p>
                                        </div>
                                    </div>

                                    {data.is_bank_account && (
                                        <div className="grid gap-4 sm:grid-cols-2">
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
                                                    placeholder="Banco Popular"
                                                    className={
                                                        errors.bank_name
                                                            ? 'border-red-500'
                                                            : ''
                                                    }
                                                />
                                                {errors.bank_name && (
                                                    <p className="mt-1 text-sm text-red-500">
                                                        {errors.bank_name}
                                                    </p>
                                                )}
                                            </div>
                                            <div>
                                                <Label htmlFor="bank_account_number">
                                                    Número de Cuenta
                                                </Label>
                                                <Input
                                                    id="bank_account_number"
                                                    value={
                                                        data.bank_account_number
                                                    }
                                                    onChange={(e) =>
                                                        setData(
                                                            'bank_account_number',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="123-456789-0"
                                                    className={
                                                        errors.bank_account_number
                                                            ? 'border-red-500'
                                                            : ''
                                                    }
                                                />
                                                {errors.bank_account_number && (
                                                    <p className="mt-1 text-sm text-red-500">
                                                        {
                                                            errors.bank_account_number
                                                        }
                                                    </p>
                                                )}
                                            </div>
                                        </div>
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
                                    placeholder="Descripción opcional..."
                                    rows={2}
                                    className={
                                        errors.description
                                            ? 'border-red-500'
                                            : ''
                                    }
                                />
                                {errors.description && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {errors.description}
                                    </p>
                                )}
                            </div>

                            {/* Actions */}
                            <div className="flex gap-3 pt-4">
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1"
                                >
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing
                                        ? 'Guardando...'
                                        : 'Crear Cuenta'}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href="/accounting/accounts">
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
