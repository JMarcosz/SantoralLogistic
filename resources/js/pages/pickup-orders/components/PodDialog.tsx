import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { Camera, Eraser, MapPin, PenTool, Upload } from 'lucide-react';
import { ChangeEvent, useCallback, useRef, useState } from 'react';
import SignatureCanvas from 'react-signature-canvas';

interface PodDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    orderType: 'pickup' | 'delivery';
    orderId: number;
}

type CaptureMode = 'photo' | 'signature';

export default function PodDialog({
    open,
    onOpenChange,
    orderType,
    orderId,
}: PodDialogProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [happenedAt, setHappenedAt] = useState(() => {
        const now = new Date();
        return now.toISOString().slice(0, 16);
    });
    const [notes, setNotes] = useState('');
    const [latitude, setLatitude] = useState('');
    const [longitude, setLongitude] = useState('');
    const [captureMode, setCaptureMode] = useState<CaptureMode>('signature');
    const [imageFile, setImageFile] = useState<File | null>(null);
    const [imagePreview, setImagePreview] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const signatureRef = useRef<SignatureCanvas>(null);

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setImageFile(file);
            const reader = new FileReader();
            reader.onload = (event) => {
                setImagePreview(event.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleClearSignature = () => {
        signatureRef.current?.clear();
    };

    const handleGetLocation = () => {
        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    setLatitude(position.coords.latitude.toFixed(7));
                    setLongitude(position.coords.longitude.toFixed(7));
                },
                (error) => {
                    console.error('Error getting location:', error);
                    alert(
                        'No se pudo obtener la ubicación. Por favor ingrese manualmente.',
                    );
                },
            );
        } else {
            alert('La geolocalización no está disponible en este navegador.');
        }
    };

    const dataURLtoFile = useCallback(
        (dataUrl: string, filename: string): File => {
            const arr = dataUrl.split(',');
            const mime = arr[0].match(/:(.*?);/)?.[1] || 'image/png';
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new File([u8arr], filename, { type: mime });
        },
        [],
    );

    const handleSubmit = () => {
        setIsSubmitting(true);

        const formData = new FormData();
        formData.append('happened_at', happenedAt);
        if (notes) formData.append('notes', notes);
        if (latitude) formData.append('latitude', latitude);
        if (longitude) formData.append('longitude', longitude);

        // Handle image based on capture mode
        if (captureMode === 'photo' && imageFile) {
            formData.append('image', imageFile);
        } else if (
            captureMode === 'signature' &&
            signatureRef.current &&
            !signatureRef.current.isEmpty()
        ) {
            const signatureDataUrl =
                signatureRef.current.toDataURL('image/png');
            const signatureFile = dataURLtoFile(
                signatureDataUrl,
                `signature_${Date.now()}.png`,
            );
            formData.append('image', signatureFile);
        }

        const url =
            orderType === 'pickup'
                ? `/pickup-orders/${orderId}/pod`
                : `/delivery-orders/${orderId}/pod`;

        router.post(url, formData, {
            forceFormData: true,
            onSuccess: () => {
                onOpenChange(false);
                resetForm();
            },
            onFinish: () => setIsSubmitting(false),
        });
    };

    const resetForm = () => {
        setHappenedAt(new Date().toISOString().slice(0, 16));
        setNotes('');
        setLatitude('');
        setLongitude('');
        setImageFile(null);
        setImagePreview(null);
        setCaptureMode('signature');
        signatureRef.current?.clear();
    };

    const handleClose = () => {
        resetForm();
        onOpenChange(false);
    };

    const hasSignature =
        signatureRef.current && !signatureRef.current.isEmpty();
    const hasImage = imageFile !== null;
    const hasEvidence = captureMode === 'signature' ? hasSignature : hasImage;

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[550px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Camera className="h-5 w-5" />
                        Registrar POD
                    </DialogTitle>
                    <DialogDescription>
                        Capture la evidencia de{' '}
                        {orderType === 'pickup' ? 'recogida' : 'entrega'}. La
                        orden será marcada como completada.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    {/* DateTime */}
                    <div className="space-y-2">
                        <Label htmlFor="happened_at">Fecha y Hora *</Label>
                        <Input
                            id="happened_at"
                            type="datetime-local"
                            value={happenedAt}
                            onChange={(e) => setHappenedAt(e.target.value)}
                            required
                        />
                    </div>

                    {/* Capture Mode Tabs */}
                    <div className="space-y-2">
                        <Label>Evidencia</Label>
                        <div className="flex gap-1 rounded-lg bg-muted p-1">
                            <button
                                type="button"
                                onClick={() => setCaptureMode('signature')}
                                className={cn(
                                    'flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    captureMode === 'signature'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <PenTool className="h-4 w-4" />
                                Firma
                            </button>
                            <button
                                type="button"
                                onClick={() => setCaptureMode('photo')}
                                className={cn(
                                    'flex flex-1 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    captureMode === 'photo'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <Camera className="h-4 w-4" />
                                Foto
                            </button>
                        </div>
                    </div>

                    {/* Signature Pad */}
                    {captureMode === 'signature' && (
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label>Firma del Receptor</Label>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleClearSignature}
                                >
                                    <Eraser className="mr-1 h-3 w-3" />
                                    Limpiar
                                </Button>
                            </div>
                            <div className="rounded-lg border-2 border-dashed bg-white">
                                <SignatureCanvas
                                    ref={signatureRef}
                                    penColor="black"
                                    canvasProps={{
                                        className: 'w-full h-40 rounded-lg',
                                        style: {
                                            width: '100%',
                                            height: '160px',
                                        },
                                    }}
                                />
                            </div>
                            <p className="text-center text-xs text-muted-foreground">
                                Dibuje la firma con el mouse o el dedo
                            </p>
                        </div>
                    )}

                    {/* Photo Upload */}
                    {captureMode === 'photo' && (
                        <div className="space-y-2">
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/*"
                                capture="environment"
                                onChange={handleFileChange}
                                className="hidden"
                            />
                            <div
                                onClick={() => fileInputRef.current?.click()}
                                className="cursor-pointer rounded-lg border-2 border-dashed p-4 text-center transition-colors hover:border-primary"
                            >
                                {imagePreview ? (
                                    <div className="space-y-2">
                                        <img
                                            src={imagePreview}
                                            alt="Preview"
                                            className="mx-auto max-h-40 rounded"
                                        />
                                        <p className="text-sm text-muted-foreground">
                                            Click para cambiar
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-2 py-4">
                                        <Upload className="mx-auto h-8 w-8 text-muted-foreground" />
                                        <p className="text-sm text-muted-foreground">
                                            Click para tomar foto o subir imagen
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            JPG, PNG, GIF, WEBP (Max 10MB)
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Location */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label>Ubicación (Opcional)</Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleGetLocation}
                            >
                                <MapPin className="mr-1 h-3 w-3" />
                                Obtener GPS
                            </Button>
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <Input
                                placeholder="Latitud"
                                value={latitude}
                                onChange={(e) => setLatitude(e.target.value)}
                                type="number"
                                step="any"
                            />
                            <Input
                                placeholder="Longitud"
                                value={longitude}
                                onChange={(e) => setLongitude(e.target.value)}
                                type="number"
                                step="any"
                            />
                        </div>
                    </div>

                    {/* Notes */}
                    <div className="space-y-2">
                        <Label htmlFor="notes">Notas (Opcional)</Label>
                        <Textarea
                            id="notes"
                            placeholder="Observaciones adicionales..."
                            value={notes}
                            onChange={(e) => setNotes(e.target.value)}
                            rows={2}
                        />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={handleClose}>
                        Cancelar
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={isSubmitting || !happenedAt}
                        className="bg-green-600 hover:bg-green-700"
                    >
                        {isSubmitting ? 'Registrando...' : 'Registrar POD'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
