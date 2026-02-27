import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { image as podImageRoute } from '@/routes/pods';
import {
    AlertCircle,
    CheckCircle,
    Clock,
    ExternalLink,
    Image as ImageIcon,
    MapPin,
    User,
} from 'lucide-react';
import { useState } from 'react';

interface Pod {
    id: number;
    happened_at: string;
    latitude: string | null;
    longitude: string | null;
    image_path: string | null;
    notes: string | null;
    created_by: {
        id: number;
        name: string;
    } | null;
    created_at: string;
}

interface PodDisplayProps {
    pod: Pod;
    orderType: 'pickup' | 'delivery';
}

export default function PodDisplay({ pod, orderType }: PodDisplayProps) {
    const [imageModalOpen, setImageModalOpen] = useState(false);
    const [imageError, setImageError] = useState(false);

    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleString('es-DO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const hasLocation = pod.latitude && pod.longitude;
    // Usar el helper de rutas de Wayfinder para construir la URL de la imagen
    const imageUrl = pod.image_path ? podImageRoute.url({ pod: pod.id }) : null;

    const handleImageError = () => {
        setImageError(true);
    };

    return (
        <>
            <Card className="border-green-200 bg-green-50/50 dark:border-green-800 dark:bg-green-950/20">
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-green-700 dark:text-green-400">
                        <CheckCircle className="h-5 w-5" />
                        Prueba de{' '}
                        {orderType === 'pickup' ? 'Recogida' : 'Entrega'}
                        <Badge
                            variant="secondary"
                            className="ml-auto bg-green-100 text-green-700"
                        >
                            Completado
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        {/* Timestamp */}
                        <div className="flex items-start gap-2">
                            <Clock className="mt-0.5 h-4 w-4 text-muted-foreground" />
                            <div>
                                <Label className="text-xs text-muted-foreground">
                                    Fecha y Hora
                                </Label>
                                <p className="text-sm font-medium">
                                    {formatDateTime(pod.happened_at)}
                                </p>
                            </div>
                        </div>

                        {/* Registered by */}
                        {pod.created_by && (
                            <div className="flex items-start gap-2">
                                <User className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div>
                                    <Label className="text-xs text-muted-foreground">
                                        Registrado por
                                    </Label>
                                    <p className="text-sm font-medium">
                                        {pod.created_by.name}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* Location */}
                        {hasLocation && (
                            <div className="flex items-start gap-2">
                                <MapPin className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div>
                                    <Label className="text-xs text-muted-foreground">
                                        Ubicación
                                    </Label>
                                    <p className="text-sm font-medium">
                                        {pod.latitude}, {pod.longitude}
                                    </p>
                                    <a
                                        href={`https://www.google.com/maps?q=${pod.latitude},${pod.longitude}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                    >
                                        Ver en mapa
                                        <ExternalLink className="h-3 w-3" />
                                    </a>
                                </div>
                            </div>
                        )}

                        {/* Image */}
                        {imageUrl ? (
                            <div className="flex items-start gap-2 sm:col-span-2">
                                <ImageIcon className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div className="flex-1">
                                    <Label className="text-xs text-muted-foreground">
                                        Evidencia
                                    </Label>
                                    <div className="mt-2 flex items-start gap-3">
                                        {/* Thumbnail */}
                                        <button
                                            onClick={() =>
                                                setImageModalOpen(true)
                                            }
                                            className="group relative overflow-hidden rounded-lg border-2 border-muted transition-all hover:border-green-500 hover:shadow-md"
                                        >
                                            <img
                                                src={imageUrl}
                                                alt="POD thumbnail"
                                                className="h-24 w-24 object-cover transition-transform group-hover:scale-105"
                                                onError={handleImageError}
                                            />
                                            <div className="absolute inset-0 flex items-center justify-center bg-black/0 transition-all group-hover:bg-black/20">
                                                <ExternalLink className="h-5 w-5 text-white opacity-0 transition-opacity group-hover:opacity-100" />
                                            </div>
                                        </button>
                                        <div className="flex flex-col gap-1">
                                            <Button
                                                variant="link"
                                                size="sm"
                                                className="h-auto justify-start p-0 text-sm font-medium text-green-600"
                                                onClick={() =>
                                                    setImageModalOpen(true)
                                                }
                                            >
                                                Ver imagen completa
                                            </Button>
                                            <p className="text-xs text-muted-foreground">
                                                Click en la imagen para
                                                ampliar
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="flex items-start gap-2 sm:col-span-2">
                                <ImageIcon className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                <div>
                                    <Label className="text-xs text-muted-foreground">
                                        Evidencia
                                    </Label>
                                    <p className="text-sm text-muted-foreground italic">
                                        No hay imagen adjunta al POD
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Notes */}
                    {pod.notes && (
                        <div className="border-t pt-3">
                            <Label className="text-xs text-muted-foreground">
                                Notas
                            </Label>
                            <p className="mt-1 text-sm whitespace-pre-wrap">
                                {pod.notes}
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Image Viewer Modal */}
            {imageUrl && (
                <Dialog open={imageModalOpen} onOpenChange={setImageModalOpen}>
                    <DialogContent className="max-w-4xl">
                        <DialogHeader>
                            <DialogTitle className="flex items-center gap-2">
                                <ImageIcon className="h-5 w-5" />
                                Evidencia POD -{' '}
                                {orderType === 'pickup'
                                    ? 'Recogida'
                                    : 'Entrega'}
                            </DialogTitle>
                        </DialogHeader>
                        <div className="flex min-h-[400px] items-center justify-center rounded-lg bg-muted/30 p-4">
                            {imageError ? (
                                <div className="flex flex-col items-center gap-3 text-muted-foreground">
                                    <AlertCircle className="h-12 w-12" />
                                    <p className="text-sm font-medium">
                                        Error al cargar la imagen
                                    </p>
                                    <p className="text-xs">
                                        Intenta abrir en nueva pestaña o
                                        recarga la página
                                    </p>
                                </div>
                            ) : (
                                <img
                                    src={imageUrl}
                                    alt="POD Evidence"
                                    className="max-h-[70vh] w-full rounded-lg object-contain"
                                    onError={handleImageError}
                                />
                            )}
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row sm:justify-between">
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Clock className="h-3 w-3" />
                                <span>
                                    Registrado:{' '}
                                    {formatDateTime(pod.happened_at)}
                                </span>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => setImageModalOpen(false)}
                                >
                                    Cerrar
                                </Button>
                                <Button asChild variant="default">
                                    <a
                                        href={imageUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <ExternalLink className="mr-2 h-4 w-4" />
                                        Abrir en nueva pestaña
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            )}
        </>
    );
}
