/* eslint-disable @typescript-eslint/no-unused-vars */
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
import { Link, router } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Edit,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    normal_balance: string;
    level: number;
    parent_id: number | null;
    is_postable: boolean;
    requires_subsidiary: boolean;
    is_active: boolean;
    description?: string;
    children?: Account[];
}

interface Props {
    accounts: Account[];
    allAccounts: Account[];
    filters: {
        search?: string;
        type?: string;
        status?: string;
    };
    accountTypes: Array<{ value: string; label: string }>;
}

export default function AccountsIndex({
    accounts,
    allAccounts,
    filters,
    accountTypes,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [typeFilter, setTypeFilter] = useState(filters.type || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [expandedNodes, setExpandedNodes] = useState<Set<number>>(new Set());

    const handleDelete = (account: Account) => {
        if (confirm(`¿Eliminar cuenta ${account.code} - ${account.name}?`)) {
            router.delete(`/accounting/accounts/${account.id}`, {
                preserveScroll: true,
            });
        }
    };

    const handleSearch = () => {
        router.get(
            '/accounting/accounts',
            {
                search,
                type: typeFilter !== 'all' ? typeFilter : undefined,
                status: statusFilter !== 'all' ? statusFilter : undefined,
            },
            { preserveState: true },
        );
    };

    const toggleNode = (id: number) => {
        const newExpanded = new Set(expandedNodes);
        if (newExpanded.has(id)) {
            newExpanded.delete(id);
        } else {
            newExpanded.add(id);
        }
        setExpandedNodes(newExpanded);
    };

    const getTypeColor = (type: string) => {
        const colors: Record<string, string> = {
            asset: 'text-blue-600 bg-blue-50 dark:bg-blue-950',
            liability: 'text-red-600 bg-red-50 dark:bg-red-950',
            equity: 'text-purple-600 bg-purple-50 dark:bg-purple-950',
            revenue: 'text-green-600 bg-green-50 dark:bg-green-950',
            expense: 'text-orange-600 bg-orange-50 dark:bg-orange-950',
        };
        return colors[type] || '';
    };

    const renderTreeNode = (account: Account, depth = 0) => {
        const hasChildren = account.children && account.children.length > 0;
        const isExpanded = expandedNodes.has(account.id);
        const indent = depth * 24;

        return (
            <div key={account.id}>
                <div
                    className={`flex items-center gap-2 border-b py-2 transition-colors hover:bg-muted/50 ${
                        !account.is_active ? 'opacity-50' : ''
                    }`}
                    style={{ paddingLeft: `${indent}px` }}
                >
                    {/* Expand/Collapse */}
                    <div className="w-6">
                        {hasChildren && (
                            <button
                                onClick={() => toggleNode(account.id)}
                                className="rounded p-0.5 hover:bg-muted"
                            >
                                {isExpanded ? (
                                    <ChevronDown className="h-4 w-4" />
                                ) : (
                                    <ChevronRight className="h-4 w-4" />
                                )}
                            </button>
                        )}
                    </div>

                    {/* Code */}
                    <div className="w-32 font-mono text-sm font-semibold">
                        {account.code}
                    </div>

                    {/* Name */}
                    <div
                        className={`flex-1 ${
                            !account.is_postable ? 'font-bold' : ''
                        }`}
                    >
                        {account.name}
                    </div>

                    {/* Type Badge */}
                    <div
                        className={`rounded-full px-2 py-0.5 text-xs font-medium ${getTypeColor(
                            account.type,
                        )}`}
                    >
                        {
                            accountTypes.find((t) => t.value === account.type)
                                ?.label
                        }
                    </div>

                    {/* Balance */}
                    <div className="w-20 text-center text-xs text-muted-foreground">
                        {account.normal_balance === 'debit' ? 'D' : 'C'}
                    </div>

                    {/* Postable */}
                    <div className="w-24 text-center">
                        {account.is_postable ? (
                            <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300">
                                Posteable
                            </span>
                        ) : (
                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                Grupo
                            </span>
                        )}
                    </div>

                    {/* Active */}
                    {!account.is_active && (
                        <span className="text-xs text-muted-foreground">
                            Inactiva
                        </span>
                    )}

                    {/* Actions */}
                    <div className="flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8"
                            asChild
                        >
                            <Link
                                href={`/accounting/accounts/${account.id}/edit`}
                            >
                                <Edit className="h-4 w-4" />
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 text-red-600 hover:bg-red-50 hover:text-red-700"
                            onClick={() => handleDelete(account)}
                        >
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* Render Children */}
                {hasChildren && isExpanded && (
                    <div>
                        {account.children!.map((child) =>
                            renderTreeNode(child, depth + 1),
                        )}
                    </div>
                )}
            </div>
        );
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Contabilidad', href: '/accounting' },
                { title: 'Plan de Cuentas', href: '/accounting/accounts' },
            ]}
        >
            <div className="flex flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">Plan de Cuentas</h1>
                        <p className="text-sm text-muted-foreground">
                            Catálogo jerárquico de cuentas contables
                        </p>
                    </div>
                    <Button size="lg" asChild>
                        <Link href="/accounting/accounts/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Cuenta
                        </Link>
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    placeholder="Buscar por código o nombre..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) =>
                                        e.key === 'Enter' && handleSearch()
                                    }
                                />
                            </div>
                            <Select
                                value={typeFilter}
                                onValueChange={setTypeFilter}
                            >
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder="Tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        Todos los tipos
                                    </SelectItem>
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
                            <Select
                                value={statusFilter}
                                onValueChange={setStatusFilter}
                            >
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas</SelectItem>
                                    <SelectItem value="active">
                                        Activas
                                    </SelectItem>
                                    <SelectItem value="inactive">
                                        Inactivas
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch}>
                                <Search className="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Tree View */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle>
                                Cuentas ({accounts.length} encontradas)
                            </CardTitle>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    const allIds = new Set<number>();
                                    const collectIds = (accs: Account[]) => {
                                        accs.forEach((acc) => {
                                            if (
                                                acc.children &&
                                                acc.children.length > 0
                                            ) {
                                                allIds.add(acc.id);
                                                collectIds(acc.children);
                                            }
                                        });
                                    };
                                    collectIds(accounts);
                                    setExpandedNodes(allIds);
                                }}
                            >
                                Expandir Todo
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {/* Header Row */}
                        <div className="mb-2 flex items-center gap-2 border-b-2 pb-2 text-sm font-semibold text-muted-foreground">
                            <div className="w-6"></div>
                            <div className="w-32">Código</div>
                            <div className="flex-1">Nombre</div>
                            <div className="w-24 text-center">Tipo</div>
                            <div className="w-20 text-center">Balance</div>
                            <div className="w-24 text-center">Estado</div>
                            <div className="w-24 text-center">Acciones</div>
                        </div>

                        {/* Tree Nodes */}
                        <div>
                            {accounts.length > 0 ? (
                                accounts.map((account) =>
                                    renderTreeNode(account),
                                )
                            ) : (
                                <div className="py-8 text-center text-muted-foreground">
                                    No se encontraron cuentas
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
