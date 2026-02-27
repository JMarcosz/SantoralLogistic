import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ProductService } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Check, FileText, X } from 'lucide-react';
import QuoteForm from './components/quote-form';

interface Customer {
    id: number;
    name: string;
    code: string | null;
}

interface Port {
    id: number;
    code: string;
    name: string;
    country: string;
    type: 'air' | 'ocean' | 'ground';
}

interface TransportMode {
    id: number;
    code: string;
    name: string;
}

interface ServiceType {
    id: number;
    code: string;
    name: string;
}

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface Term {
    id: number;
    code: string;
    name: string;
}

interface Props {
    customers: Customer[];
    ports: Port[];
    transportModes: TransportMode[];
    serviceTypes: ServiceType[];
    currencies: Currency[];
    productsServices: ProductService[];
    paymentTerms: Term[];
    paymentTerms: Term[];
    footerTerms: Term[];
    projects: { id: number; name: string; code: string }[];
    carriers: { id: number; name: string; code: string }[];
    issuingCompanies: { id: number; name: string }[];
    divisions: { value: string; label: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cotizaciones', href: '/quotes' },
    { title: 'Nueva Cotización', href: '/quotes/create' },
];

export default function QuoteCreate({
    customers,
    ports,
    transportModes,
    serviceTypes,
    currencies,
    productsServices,
    paymentTerms,
    footerTerms,
    projects,
    carriers,
    issuingCompanies,
    divisions,
}: Props) {
    const { flash } = usePage().props as {
        flash?: { success?: string; error?: string };
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Cotización" />

            <div className="container mx-auto space-y-6 px-4 py-6">
                {/* Flash Messages */}
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

                {/* Header */}
                <div className="relative overflow-hidden rounded-xl border border-primary/20 bg-gradient-to-br from-card via-card to-primary/5 p-6">
                    <div className="absolute top-0 right-0 h-32 w-32 translate-x-8 -translate-y-8 rounded-full bg-primary/10 blur-3xl" />

                    <div className="relative flex items-center gap-4">
                        <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/50">
                            <FileText className="h-7 w-7 text-primary-foreground" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">
                                Nueva Cotización
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Complete los campos para crear una nueva
                                cotización
                            </p>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <QuoteForm
                    customers={customers}
                    ports={ports}
                    transportModes={transportModes}
                    serviceTypes={serviceTypes}
                    currencies={currencies}
                    productsServices={productsServices}
                    paymentTerms={paymentTerms}
                    paymentTerms={paymentTerms}
                    footerTerms={footerTerms}
                    projects={projects}
                    carriers={carriers}
                    issuingCompanies={issuingCompanies}
                    divisions={divisions}
                    mode="create"
                />
            </div>
        </AppLayout>
    );
}
