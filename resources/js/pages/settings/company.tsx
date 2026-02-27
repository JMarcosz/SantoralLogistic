import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, router, useForm } from '@inertiajs/react';
import {
    Building2,
    Check,
    Globe,
    ImagePlus,
    Loader2,
    Mail,
    MapPin,
    Phone,
    Save,
    Trash2,
    Upload,
} from 'lucide-react';
import { useCallback, useRef, useState } from 'react';

import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

interface CompanySetting {
    id: number;
    name: string;
    rnc: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    logo_url: string | null;
    is_active: boolean;
}

interface Props {
    company: CompanySetting;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/settings/profile',
    },
    {
        title: 'Empresa',
        href: '/settings/company',
    },
];

export default function Company({ company }: Props) {
    const [logoPreview, setLogoPreview] = useState<string | null>(
        company.logo_url,
    );
    const [isDragging, setIsDragging] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, recentlySuccessful } =
        useForm({
            name: company.name || '',
            rnc: company.rnc || '',
            address: company.address || '',
            phone: company.phone || '',
            email: company.email || '',
            website: company.website || '',
            logo: null as File | null,
        });

    const handleLogoChange = useCallback(
        (file: File | null) => {
            if (file) {
                const validTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/jpg',
                    'image/gif',
                    'image/svg+xml',
                    'image/webp',
                ];
                if (!validTypes.includes(file.type)) {
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    return;
                }

                setData('logo', file);
                const reader = new FileReader();
                reader.onloadend = () => {
                    setLogoPreview(reader.result as string);
                };
                reader.readAsDataURL(file);
            }
        },
        [setData],
    );

    const handleDrop = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            setIsDragging(false);
            const file = e.dataTransfer.files[0];
            handleLogoChange(file);
        },
        [handleLogoChange],
    );

    const handleDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    }, []);

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    }, []);

    const handleFileSelect = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            const file = e.target.files?.[0];
            if (file) {
                handleLogoChange(file);
            }
        },
        [handleLogoChange],
    );

    const handleDeleteLogo = useCallback(() => {
        setIsDeleting(true);
        router.delete('/settings/company/logo', {
            preserveScroll: true,
            onSuccess: () => {
                setLogoPreview(null);
                setData('logo', null);
            },
            onFinish: () => {
                setIsDeleting(false);
            },
        });
    }, [setData]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/settings/company', {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración de Empresa" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Compact Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center gap-4">
                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                            <Building2 className="h-7 w-7 text-primary-foreground" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                Información de la Empresa
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Datos para facturas, cotizaciones y documentos
                            </p>
                        </div>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* Two Column Layout for larger screens */}
                    <div className="grid gap-6 lg:grid-cols-3">
                        {/* Logo Section - Smaller Card */}
                        <Card className="overflow-hidden border border-primary/20 lg:col-span-1">
                            <CardContent className="p-5">
                                <h3 className="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                    Logo
                                </h3>

                                <div className="flex flex-col items-center gap-4">
                                    {/* Logo Preview - Smaller */}
                                    <div className="group relative">
                                        <Avatar className="h-24 w-24 rounded-xl border-2 border-primary/20 bg-muted/50 shadow-md">
                                            {logoPreview ? (
                                                <AvatarImage
                                                    src={logoPreview}
                                                    alt={company.name}
                                                    className="object-cover"
                                                />
                                            ) : (
                                                <AvatarFallback className="rounded-xl bg-gradient-to-br from-primary/20 to-primary/5 text-xl font-bold text-primary">
                                                    {data.name ? (
                                                        getInitials(data.name)
                                                    ) : (
                                                        <Building2 className="h-10 w-10" />
                                                    )}
                                                </AvatarFallback>
                                            )}
                                        </Avatar>
                                        {logoPreview && (
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="icon"
                                                className="absolute -top-2 -right-2 h-7 w-7 rounded-full opacity-0 shadow-md transition-opacity group-hover:opacity-100"
                                                onClick={handleDeleteLogo}
                                                disabled={isDeleting}
                                            >
                                                {isDeleting ? (
                                                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                                ) : (
                                                    <Trash2 className="h-3.5 w-3.5" />
                                                )}
                                            </Button>
                                        )}
                                    </div>

                                    {/* Compact Upload Zone */}
                                    <div
                                        onDrop={handleDrop}
                                        onDragOver={handleDragOver}
                                        onDragLeave={handleDragLeave}
                                        onClick={() =>
                                            fileInputRef.current?.click()
                                        }
                                        className={`w-full cursor-pointer rounded-lg border-2 border-dashed p-4 text-center transition-all ${
                                            isDragging
                                                ? 'scale-[1.02] border-primary bg-primary/10'
                                                : 'border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/50'
                                        }`}
                                    >
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp"
                                            onChange={handleFileSelect}
                                            className="hidden"
                                        />
                                        <div className="flex flex-col items-center gap-2">
                                            <div
                                                className={`rounded-full p-2 ${isDragging ? 'bg-primary/20' : 'bg-muted'}`}
                                            >
                                                {isDragging ? (
                                                    <ImagePlus className="h-5 w-5 text-primary" />
                                                ) : (
                                                    <Upload className="h-5 w-5 text-muted-foreground" />
                                                )}
                                            </div>
                                            <p className="text-xs font-medium">
                                                {isDragging
                                                    ? 'Suelta aquí'
                                                    : 'Arrastra o haz clic'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                PNG, JPG, SVG (máx 2MB)
                                            </p>
                                        </div>
                                    </div>
                                    <InputError
                                        className="text-center"
                                        message={errors.logo}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Company Info Section - 2/3 width */}
                        <Card className="overflow-hidden border border-blue-500/20 lg:col-span-2">
                            <CardContent className="p-5">
                                <h3 className="mb-4 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                    Datos de la Empresa
                                </h3>

                                <div className="grid gap-4">
                                    {/* Row 1: Name + RNC */}
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="name"
                                                className="flex items-center gap-1.5 text-sm"
                                            >
                                                <Building2 className="h-3.5 w-3.5 text-primary" />
                                                Nombre
                                                <span className="text-destructive">
                                                    *
                                                </span>
                                            </Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) =>
                                                    setData(
                                                        'name',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Mi Empresa S.R.L."
                                                required
                                                className="h-10"
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="rnc"
                                                className="text-sm"
                                            >
                                                RNC / ID Fiscal
                                            </Label>
                                            <Input
                                                id="rnc"
                                                value={data.rnc}
                                                onChange={(e) =>
                                                    setData(
                                                        'rnc',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="000-00000-0"
                                                className="h-10"
                                            />
                                            <InputError message={errors.rnc} />
                                        </div>
                                    </div>

                                    {/* Row 2: Address */}
                                    <div className="grid gap-1.5">
                                        <Label
                                            htmlFor="address"
                                            className="flex items-center gap-1.5 text-sm"
                                        >
                                            <MapPin className="h-3.5 w-3.5 text-primary" />
                                            Dirección
                                        </Label>
                                        <Input
                                            id="address"
                                            value={data.address}
                                            onChange={(e) =>
                                                setData(
                                                    'address',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Calle Principal #123, Ciudad"
                                            className="h-10"
                                        />
                                        <InputError message={errors.address} />
                                    </div>

                                    {/* Row 3: Phone + Email */}
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="phone"
                                                className="flex items-center gap-1.5 text-sm"
                                            >
                                                <Phone className="h-3.5 w-3.5 text-primary" />
                                                Teléfono
                                            </Label>
                                            <Input
                                                id="phone"
                                                type="tel"
                                                value={data.phone}
                                                onChange={(e) =>
                                                    setData(
                                                        'phone',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="+1 (809) 000-0000"
                                                className="h-10"
                                            />
                                            <InputError
                                                message={errors.phone}
                                            />
                                        </div>

                                        <div className="grid gap-1.5">
                                            <Label
                                                htmlFor="email"
                                                className="flex items-center gap-1.5 text-sm"
                                            >
                                                <Mail className="h-3.5 w-3.5 text-primary" />
                                                Correo Electrónico
                                            </Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                value={data.email}
                                                onChange={(e) =>
                                                    setData(
                                                        'email',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="contacto@miempresa.com"
                                                className="h-10"
                                            />
                                            <InputError
                                                message={errors.email}
                                            />
                                        </div>
                                    </div>

                                    {/* Row 4: Website */}
                                    <div className="grid gap-1.5">
                                        <Label
                                            htmlFor="website"
                                            className="flex items-center gap-1.5 text-sm"
                                        >
                                            <Globe className="h-3.5 w-3.5 text-primary" />
                                            Sitio Web
                                        </Label>
                                        <Input
                                            id="website"
                                            type="url"
                                            value={data.website}
                                            onChange={(e) =>
                                                setData(
                                                    'website',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="https://www.miempresa.com"
                                            className="h-10"
                                        />
                                        <InputError message={errors.website} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Compact Submit Section */}
                    <div className="flex items-center gap-4 rounded-lg border border-border/50 bg-card/50 p-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="min-w-[140px]"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Guardando...
                                </>
                            ) : (
                                <>
                                    <Save className="mr-2 h-4 w-4" />
                                    Guardar Cambios
                                </>
                            )}
                        </Button>

                        <Transition
                            show={recentlySuccessful}
                            enter="transition ease-in-out duration-300"
                            enterFrom="opacity-0 translate-x-2"
                            leave="transition ease-in-out duration-300"
                            leaveTo="opacity-0 translate-x-2"
                        >
                            <div className="flex items-center gap-2 rounded-lg bg-emerald-500/10 px-3 py-2">
                                <Check className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                <p className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                    Guardado
                                </p>
                            </div>
                        </Transition>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
