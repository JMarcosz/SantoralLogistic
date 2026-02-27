import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

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
}

export function PutawayDialog({
    open,
    onClose,
    itemId,
    warehouseId,
    sku,
}: Props) {
    const [locations, setLocations] = useState<Location[]>([]);
    const [selectedLocation, setSelectedLocation] = useState('');
    const [loading, setLoading] = useState(false);
    const [fetchError, setFetchError] = useState<string | null>(null);

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
        } catch (err) {
            const message =
                err instanceof Error ? err.message : 'Error desconocido';
            setFetchError(message);
            setLocations([]);
        }
    }, []);

    /**
     * Load locations when dialog opens
     */
    useEffect(() => {
        if (open && warehouseId) {
            fetchLocations(warehouseId);
        }
    }, [open, warehouseId, fetchLocations]);

    /**
     * Reset state when dialog closes
     */
    const handleClose = () => {
        setSelectedLocation('');
        setFetchError(null);
        onClose();
    };

    /**
     * Submit the putaway operation
     */
    const handleSubmit = () => {
        if (!selectedLocation) return;

        setLoading(true);
        router.post(
            `/inventory/${itemId}/putaway`,
            { location_id: selectedLocation },
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
                    <DialogTitle>Asignar Ubicación (Putaway)</DialogTitle>
                </DialogHeader>
                <div className="space-y-4 py-4">
                    <p className="text-sm text-muted-foreground">
                        SKU: <span className="font-medium">{sku}</span>
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
                        <div>
                            <Label>Ubicación *</Label>
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
                                            {loc.zone && ` (Zona ${loc.zone})`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={handleClose}>
                        Cancelar
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={loading || !selectedLocation || !!fetchError}
                    >
                        {loading ? 'Guardando...' : 'Asignar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
