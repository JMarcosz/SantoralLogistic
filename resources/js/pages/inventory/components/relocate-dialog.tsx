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
import { useCallback, useEffect, useRef, useState } from 'react';

interface Location {
    id: number;
    code: string;
    zone: string | null;
    type: string;
}

interface Props {
    open: boolean;
    onClose: () => void;
    itemId: number;
    warehouseId: number;
    sku: string;
    currentLocationCode: string;
    maxQty: number;
}

export function RelocateDialog({
    open,
    onClose,
    itemId,
    warehouseId,
    sku,
    currentLocationCode,
    maxQty,
}: Props) {
    const [locations, setLocations] = useState<Location[]>([]);
    const [selectedLocation, setSelectedLocation] = useState('');
    const [qty, setQty] = useState('');
    const [notes, setNotes] = useState('');
    const [loading, setLoading] = useState(false);
    const [fetchError, setFetchError] = useState<string | null>(null);
    const lastWarehouseRef = useRef<number | null>(null);

    /**
     * Fetch locations from the API
     */
    const fetchLocations = useCallback(async (whId: number) => {
        setFetchError(null);

        try {
            const response = await fetch(`/api/warehouses/${whId}/locations`);

            if (!response.ok) {
                throw new Error('Error al cargar ubicaciones');
            }

            const data = await response.json();
            setLocations(data);
            lastWarehouseRef.current = whId;
        } catch (err) {
            const message =
                err instanceof Error ? err.message : 'Error desconocido';
            setFetchError(message);
            setLocations([]);
        }
    }, []);

    /**
     * Load locations when dialog opens or warehouse changes
     */
    useEffect(() => {
        if (open && warehouseId && warehouseId !== lastWarehouseRef.current) {
            fetchLocations(warehouseId);
        }
    }, [open, warehouseId, fetchLocations]);

    // Use maxQty as default if qty is empty
    const displayQty = qty === '' ? maxQty.toString() : qty;

    /**
     * Reset state when dialog closes
     */
    const handleClose = () => {
        setSelectedLocation('');
        setQty('');
        setNotes('');
        setFetchError(null);
        onClose();
    };

    /**
     * Submit the relocate operation
     */
    const handleSubmit = () => {
        if (!selectedLocation || !displayQty) return;

        setLoading(true);
        router.post(
            `/inventory/${itemId}/relocate`,
            {
                to_location_id: selectedLocation,
                qty: parseFloat(displayQty),
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
                    <DialogTitle>Reubicar Inventario</DialogTitle>
                </DialogHeader>
                <div className="space-y-4 py-4">
                    <p className="text-sm text-muted-foreground">
                        SKU: <span className="font-medium">{sku}</span>
                        <br />
                        Ubicación actual:{' '}
                        <span className="font-medium">
                            {currentLocationCode}
                        </span>
                    </p>

                    {fetchError ? (
                        <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                            {fetchError}
                            <Button
                                variant="link"
                                size="sm"
                                className="ml-2 h-auto p-0"
                                onClick={() => fetchLocations(warehouseId)}
                            >
                                Reintentar
                            </Button>
                        </div>
                    ) : (
                        <>
                            <div>
                                <Label>Ubicación Destino *</Label>
                                <Select
                                    value={selectedLocation}
                                    onValueChange={setSelectedLocation}
                                >
                                    <SelectTrigger className="mt-1">
                                        <SelectValue placeholder="Seleccionar ubicación..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {locations.map((loc) => (
                                            <SelectItem
                                                key={loc.id}
                                                value={loc.id.toString()}
                                            >
                                                {loc.code}
                                                {loc.zone &&
                                                    ` (Zona ${loc.zone})`}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>
                                    Cantidad a mover * (máx: {maxQty})
                                </Label>
                                <Input
                                    type="number"
                                    value={displayQty}
                                    onChange={(e) => setQty(e.target.value)}
                                    min="0.001"
                                    max={maxQty}
                                    step="0.001"
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label>Notas (opcional)</Label>
                                <Textarea
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    rows={2}
                                    className="mt-1"
                                />
                            </div>
                        </>
                    )}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={handleClose}>
                        Cancelar
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={
                            loading ||
                            !selectedLocation ||
                            !displayQty ||
                            !!fetchError
                        }
                    >
                        {loading ? 'Moviendo...' : 'Reubicar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
