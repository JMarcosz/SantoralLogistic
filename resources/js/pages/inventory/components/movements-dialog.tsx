import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { History } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface Movement {
    id: number;
    type: string;
    type_label: string;
    from_location: string | null;
    to_location: string | null;
    qty: number;
    reference: string | null;
    notes: string | null;
    user: string | null;
    created_at: string;
}

interface Props {
    open: boolean;
    onClose: () => void;
    itemId: number | null;
    sku: string;
}

const TYPE_COLORS: Record<string, string> = {
    receive: 'bg-green-500/10 text-green-700 border-green-500/30',
    putaway: 'bg-blue-500/10 text-blue-700 border-blue-500/30',
    transfer: 'bg-purple-500/10 text-purple-700 border-purple-500/30',
    adjust: 'bg-amber-500/10 text-amber-700 border-amber-500/30',
    pick: 'bg-orange-500/10 text-orange-700 border-orange-500/30',
    return: 'bg-cyan-500/10 text-cyan-700 border-cyan-500/30',
};

export function MovementsDialog({ open, onClose, itemId, sku }: Props) {
    const [movements, setMovements] = useState<Movement[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [loadedFor, setLoadedFor] = useState<number | null>(null);

    /**
     * Fetch movements from the API
     */
    const fetchMovements = useCallback(async (id: number) => {
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(`/inventory/${id}/movements`);

            if (!response.ok) {
                throw new Error(
                    response.status === 403
                        ? 'No tiene permisos para ver el historial'
                        : 'Error al cargar el historial',
                );
            }

            const data = await response.json();
            setMovements(data.movements || []);
            setLoadedFor(id);
        } catch (err) {
            const message =
                err instanceof Error ? err.message : 'Error desconocido';
            setError(message);
            setMovements([]);
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Load movements when dialog opens for a new item
     */
    useEffect(() => {
        if (open && itemId !== null && loadedFor !== itemId) {
            fetchMovements(itemId);
        }
    }, [open, itemId, loadedFor, fetchMovements]);

    /**
     * Reset state when dialog closes
     */
    const handleOpenChange = (isOpen: boolean) => {
        if (!isOpen) {
            setLoadedFor(null);
            setMovements([]);
            setError(null);
            onClose();
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-3xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <History className="h-5 w-5" />
                        Historial de Movimientos - {sku}
                    </DialogTitle>
                </DialogHeader>
                <div className="max-h-96 overflow-auto">
                    {loading ? (
                        <p className="py-8 text-center text-muted-foreground">
                            Cargando...
                        </p>
                    ) : error ? (
                        <div className="py-8 text-center">
                            <p className="text-destructive">{error}</p>
                            <Button
                                variant="outline"
                                size="sm"
                                className="mt-2"
                                onClick={() => itemId && fetchMovements(itemId)}
                            >
                                Reintentar
                            </Button>
                        </div>
                    ) : movements.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">
                            No hay movimientos registrados
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Desde</TableHead>
                                    <TableHead>Hacia</TableHead>
                                    <TableHead className="text-right">
                                        Cantidad
                                    </TableHead>
                                    <TableHead>Referencia</TableHead>
                                    <TableHead>Usuario</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {movements.map((m) => (
                                    <TableRow key={m.id}>
                                        <TableCell className="text-sm whitespace-nowrap">
                                            {m.created_at}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className={
                                                    TYPE_COLORS[m.type] || ''
                                                }
                                            >
                                                {m.type_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {m.from_location || '-'}
                                        </TableCell>
                                        <TableCell>
                                            {m.to_location || '-'}
                                        </TableCell>
                                        <TableCell
                                            className={`text-right font-medium ${
                                                m.qty > 0
                                                    ? 'text-green-600'
                                                    : m.qty < 0
                                                      ? 'text-red-600'
                                                      : ''
                                            }`}
                                        >
                                            {m.qty > 0 ? '+' : ''}
                                            {m.qty}
                                        </TableCell>
                                        <TableCell className="text-sm">
                                            {m.reference || '-'}
                                        </TableCell>
                                        <TableCell className="text-sm">
                                            {m.user || '-'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>
                <div className="flex justify-end">
                    <Button
                        variant="outline"
                        onClick={() => handleOpenChange(false)}
                    >
                        Cerrar
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
