import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
import { Textarea } from '@/components/ui/textarea';
import { router } from '@inertiajs/react';
import React, { useState } from 'react';

const ADJUSTMENT_REASONS = [
    { value: 'count_adjustment', label: 'Ajuste por conteo físico' },
    { value: 'damage', label: 'Daño' },
    { value: 'expiration', label: 'Vencimiento' },
    { value: 'return', label: 'Devolución' },
    { value: 'correction', label: 'Corrección de error' },
    { value: 'other', label: 'Otro' },
];

interface Props {
    open: boolean;
    onClose: () => void;
    itemId: number;
    sku: string;
    currentQty: number;
}

export function AdjustDialog({
    open,
    onClose,
    itemId,
    sku,
    currentQty,
}: Props) {
    const [newQty, setNewQty] = useState(currentQty.toString());
    const [reason, setReason] = useState('');
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(false);

    // Reset state when dialog opens/closes or item changes
    React.useEffect(() => {
        if (open) {
            setNewQty(currentQty.toString());
            setReason('');
            setNotes('');
        }
    }, [open, currentQty]);

    const delta = parseFloat(newQty || '0') - currentQty;

    const handleClose = () => {
        onClose();
    };

    const handleSubmit = () => {
        if (!reason) return;

        setLoading(true);
        router.post(
            `/inventory/${itemId}/adjust`,
            {
                new_qty: parseFloat(newQty),
                reason,
                notes: notes || null,
            },
            {
                onFinish: () => {
                    setLoading(false);
                    handleClose();
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Ajustar Cantidad</DialogTitle>
                </DialogHeader>
                <div className="space-y-4 py-4">
                    <p className="text-sm text-muted-foreground">
                        SKU: <span className="font-medium">{sku}</span>
                        <br />
                        Cantidad actual:{' '}
                        <span className="font-medium">{currentQty}</span>
                    </p>
                    <div>
                        <Label>Nueva Cantidad *</Label>
                        <Input
                            type="number"
                            value={newQty}
                            onChange={(e) => setNewQty(e.target.value)}
                            min="0"
                            step="0.001"
                            className="mt-1"
                        />
                        {delta !== 0 && (
                            <p
                                className={`mt-1 text-sm ${delta > 0 ? 'text-green-600' : 'text-red-600'}`}
                            >
                                {delta > 0 ? '+' : ''}
                                {delta.toFixed(3)}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label>Motivo *</Label>
                        <Select value={reason} onValueChange={setReason}>
                            <SelectTrigger className="mt-1">
                                <SelectValue placeholder="Seleccionar motivo..." />
                            </SelectTrigger>
                            <SelectContent>
                                {ADJUSTMENT_REASONS.map((r) => (
                                    <SelectItem key={r.value} value={r.value}>
                                        {r.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label>Notas (opcional)</Label>
                        <Textarea
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            rows={2}
                            className="mt-1"
                            placeholder="Detalles adicionales..."
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={handleClose}>
                        Cancelar
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={loading || !reason}
                    >
                        {loading ? 'Guardando...' : 'Ajustar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
