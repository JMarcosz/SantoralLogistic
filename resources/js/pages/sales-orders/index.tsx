import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Check, Plus, X } from 'lucide-react';

interface SalesOrder {
    id: number;
    order_number: string;
    customer: { id: number; name: string; code: string } | null;
    currency: { id: number; code: string; symbol: string } | null;
    status: string;
    total_amount: number;
    created_at: string;
}

interface Props {
    orders: {
        data: SalesOrder[];
        links: any[];
        current_page: number;
        last_page: number;
    };
}

const statusColors: Record<string, string> = {
    draft: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
    confirmed: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    delivering: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
    delivered: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    invoiced: 'bg-purple-500/10 text-purple-400 border-purple-500/30',
    cancelled: 'bg-red-500/10 text-red-400 border-red-500/30',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    confirmed: 'Confirmada',
    delivering: 'En Entrega',
    delivered: 'Entregada',
    invoiced: 'Facturada',
    cancelled: 'Cancelada',
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Órdenes de Pedido', href: '/sales-orders' },
];

export default function SalesOrderIndex({ orders }: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    const formatNumber = (num: number | null) => {
        if (num === null || num === undefined) return '-';
        return Number(num).toLocaleString('en-US', {
            minimumFractionDigits: 2,
        });
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('es-DO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Órdenes de Pedido" />

            <div className="container mx-auto space-y-6 px-4 py-6">
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

                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight">
                        Órdenes de Pedido
                    </h1>
                    <Button asChild>
                        <Link href="/sales-orders/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Orden
                        </Link>
                    </Button>
                </div>

                <div className="rounded-lg border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>No. Orden</TableHead>
                                <TableHead>Cliente</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Total</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead className="w-[100px]">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {orders.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground py-12">
                                        No hay órdenes de pedido registradas.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                orders.data.map((order) => (
                                    <TableRow key={order.id}>
                                        <TableCell className="font-mono font-medium">
                                            <Link
                                                href={`/sales-orders/${order.id}`}
                                                className="text-primary hover:underline"
                                            >
                                                {order.order_number}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            {order.customer?.name || '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={statusColors[order.status] || ''}>
                                                {statusLabels[order.status] || order.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right font-mono">
                                            {order.currency?.symbol || '$'}
                                            {formatNumber(order.total_amount)}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {formatDate(order.created_at)}
                                        </TableCell>
                                        <TableCell>
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/sales-orders/${order.id}`}>
                                                    Ver
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
