import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2 } from 'lucide-react';

interface Account {
    id: number;
    code: string;
    name: string;
}

interface Props {
    bankAccounts: Account[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Conciliación Bancaria', href: '/accounting/bank-reconciliation' },
    {
        title: 'Nuevo Estado de Cuenta',
        href: '/accounting/bank-reconciliation/create',
    },
];

export default function BankReconciliationCreate({ bankAccounts }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        account_id: '',
        statement_date: new Date().toISOString().split('T')[0],
        period_start: '',
        period_end: '',
        reference: '',
        opening_balance: '',
        closing_balance: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/accounting/bank-reconciliation');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Estado de Cuenta" />

            <div className="mx-auto max-w-2xl space-y-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/accounting/bank-reconciliation">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                </div>

                <Card className="border-white/10 bg-slate-800/50">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-white">
                            <Building2 className="h-5 w-5 text-teal-400" />
                            Nuevo Estado de Cuenta Bancario
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Account Selection */}
                            <div>
                                <label className="mb-1.5 block text-sm font-medium text-slate-300">
                                    Cuenta Bancaria *
                                </label>
                                <Select
                                    value={data.account_id}
                                    onValueChange={(value) =>
                                        setData('account_id', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccione una cuenta" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {bankAccounts.map((account) => (
                                            <SelectItem
                                                key={account.id}
                                                value={account.id.toString()}
                                            >
                                                {account.code} - {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.account_id && (
                                    <p className="mt-1 text-sm text-red-400">
                                        {errors.account_id}
                                    </p>
                                )}
                            </div>

                            {/* Statement Date */}
                            <div>
                                <label className="mb-1.5 block text-sm font-medium text-slate-300">
                                    Fecha del Estado de Cuenta *
                                </label>
                                <Input
                                    type="date"
                                    value={data.statement_date}
                                    onChange={(e) =>
                                        setData(
                                            'statement_date',
                                            e.target.value,
                                        )
                                    }
                                />
                                {errors.statement_date && (
                                    <p className="mt-1 text-sm text-red-400">
                                        {errors.statement_date}
                                    </p>
                                )}
                            </div>

                            {/* Period */}
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="mb-1.5 block text-sm font-medium text-slate-300">
                                        Período Desde *
                                    </label>
                                    <Input
                                        type="date"
                                        value={data.period_start}
                                        onChange={(e) =>
                                            setData(
                                                'period_start',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.period_start && (
                                        <p className="mt-1 text-sm text-red-400">
                                            {errors.period_start}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label className="mb-1.5 block text-sm font-medium text-slate-300">
                                        Período Hasta *
                                    </label>
                                    <Input
                                        type="date"
                                        value={data.period_end}
                                        onChange={(e) =>
                                            setData(
                                                'period_end',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    {errors.period_end && (
                                        <p className="mt-1 text-sm text-red-400">
                                            {errors.period_end}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Reference */}
                            <div>
                                <label className="mb-1.5 block text-sm font-medium text-slate-300">
                                    Referencia
                                </label>
                                <Input
                                    value={data.reference}
                                    onChange={(e) =>
                                        setData('reference', e.target.value)
                                    }
                                    placeholder="Ej: Estado Diciembre 2025"
                                />
                            </div>

                            {/* Balances */}
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="mb-1.5 block text-sm font-medium text-slate-300">
                                        Saldo Inicial *
                                    </label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={data.opening_balance}
                                        onChange={(e) =>
                                            setData(
                                                'opening_balance',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="0.00"
                                    />
                                    {errors.opening_balance && (
                                        <p className="mt-1 text-sm text-red-400">
                                            {errors.opening_balance}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <label className="mb-1.5 block text-sm font-medium text-slate-300">
                                        Saldo Final *
                                    </label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={data.closing_balance}
                                        onChange={(e) =>
                                            setData(
                                                'closing_balance',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="0.00"
                                    />
                                    {errors.closing_balance && (
                                        <p className="mt-1 text-sm text-red-400">
                                            {errors.closing_balance}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Submit */}
                            <div className="flex justify-end gap-3 pt-4">
                                <Button variant="outline" type="button" asChild>
                                    <Link href="/accounting/bank-reconciliation">
                                        Cancelar
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Creando...'
                                        : 'Crear Estado de Cuenta'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
