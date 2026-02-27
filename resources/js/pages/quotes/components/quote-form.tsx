/* eslint-disable @typescript-eslint/no-explicit-any */
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    type Currency,
    type Customer,
    type Port,
    type ProductService,
    type Quote,
    type QuoteItem,
    type ServiceType,
    type TransportMode,
} from '@/types';
import { useForm } from '@inertiajs/react';
import { AlertCircle, Save } from 'lucide-react';
import { useCallback, useEffect, useMemo } from 'react';
import { QuoteCommoditiesTab } from './quote-commodities-tab';
import { QuoteFinancialsTab } from './quote-financials-tab';
import { QuoteGeneralTab } from './quote-general-tab';
import { QuoteRoutingTab } from './quote-routing-tab';
import {
    Carrier,
    Division,
    FormQuoteLine,
    IssuingCompany,
    Project,
} from './types';

interface Props {
    quote?: Quote;
    customers: Customer[];
    ports: Port[];
    transportModes: TransportMode[];
    serviceTypes: ServiceType[];
    currencies: Currency[];
    productsServices: ProductService[];
    paymentTerms?: any[];
    footerTerms?: any[];
    projects?: Project[];
    issuingCompanies?: IssuingCompany[];
    carriers?: Carrier[];
    divisions?: Division[];
    mode: 'create' | 'edit';
}

export type QuoteFormValues = {
    customer_id: number | '';
    contact_id: number | '';
    shipper_id: number | ''; // New
    consignee_id: number | ''; // New
    origin_port_id: number | '';
    destination_port_id: number | '';
    transport_mode_id: number | '';
    service_type_id: number | '';
    currency_id: number | '';
    project_id: number | ''; // New
    issuing_company_id: number | ''; // New
    carrier_id: number | ''; // New
    division_id: number | ''; // New, renamed from division
    pickup_address: string; // New
    delivery_address: string; // New
    transit_days: number | ''; // New
    incoterms: string; // New
    valid_until: string;
    notes: string;
    terms: string;
    payment_terms_id: number | '';
    footer_terms_id: number | '';
    total_pieces: number | '';
    total_weight_kg: number | '';
    total_volume_cbm: number | '';
    lines: FormQuoteLine[];
    items: QuoteItem[];
};

export default function QuoteForm({
    quote,
    customers,
    ports,
    transportModes,
    serviceTypes,
    currencies,
    productsServices,
    paymentTerms = [],
    footerTerms = [],
    projects = [],
    issuingCompanies = [],
    carriers = [],
    divisions = [],
    mode,
}: Props) {
    const isEdit = mode === 'edit';

    const { data, setData, post, put, processing, errors } =
        useForm<QuoteFormValues>({
            customer_id: quote?.customer_id ?? '',
            contact_id: quote?.contact_id ?? '',
            shipper_id: quote?.shipper_id ?? '',
            consignee_id: quote?.consignee_id ?? '',
            pickup_address: quote?.pickup_address ?? '',
            delivery_address: quote?.delivery_address ?? '',
            origin_port_id: quote?.origin_port_id ?? '',
            destination_port_id: quote?.destination_port_id ?? '',
            transport_mode_id: quote?.transport_mode_id ?? '',
            service_type_id: quote?.service_type_id ?? '',
            currency_id:
                quote?.currency_id ??
                currencies.find((c) => c.code === 'USD')?.id ??
                '',
            project_id: quote?.project_id ?? '',
            issuing_company_id: quote?.issuing_company_id ?? '',
            carrier_id: quote?.carrier_id ?? '',
            division_id: quote?.division_id ?? '',
            transit_days: quote?.transit_days ?? '',
            incoterms: quote?.incoterms ?? '',
            valid_until: quote?.valid_until ?? '',
            notes: quote?.notes ?? '',
            terms: quote?.terms ?? '',
            payment_terms_id: quote?.payment_terms_id ?? '',
            footer_terms_id: quote?.footer_terms_id ?? '',
            total_pieces: quote?.total_pieces ?? '',
            total_weight_kg: quote?.total_weight_kg ?? '',
            total_volume_cbm: quote?.total_volume_cbm ?? '',
            lines: (quote?.lines ?? [
                {
                    product_service_id: '',
                    description: '',
                    quantity: 1,
                    unit_price: 0,
                    discount_percent: 0,
                    tax_rate: 18,
                },
            ]) as FormQuoteLine[],
            items: (quote?.items ?? []) as QuoteItem[],
        });

    // Add a new line
    const addLine = useCallback(() => {
        setData('lines', [
            ...data.lines,
            {
                product_service_id: '',
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_percent: 0,
                tax_rate: 18,
            },
        ]);
    }, [data.lines, setData]);

    // Remove a line
    const removeLine = useCallback(
        (index: number) => {
            if (data.lines.length > 1) {
                setData(
                    'lines',
                    data.lines.filter((_, i) => i !== index),
                );
            }
        },
        [data.lines, setData],
    );

    // Update a line
    const updateLine = useCallback(
        (index: number, field: keyof FormQuoteLine, value: string | number) => {
            const newLines = [...data.lines];
            newLines[index] = { ...newLines[index], [field]: value };

            // Auto-fill price and tax from product
            if (field === 'product_service_id' && typeof value === 'number') {
                const product = productsServices.find((p) => p.id === value);
                if (product) {
                    newLines[index].unit_price =
                        product.default_unit_price ?? 0;
                    newLines[index].tax_rate = product.taxable ? 18 : 0;
                    newLines[index].description = product.name;
                }
            }

            setData('lines', newLines);
        },
        [data.lines, setData, productsServices],
    );

    // Calculate line total
    const calculateLineTotal = useCallback((line: FormQuoteLine) => {
        const subtotal = line.quantity * line.unit_price;
        const discount = subtotal * (line.discount_percent / 100);
        return subtotal - discount;
    }, []);

    // Calculate totals
    const totals = useMemo(() => {
        let subtotal = 0;
        let taxAmount = 0;

        data.lines.forEach((line) => {
            const lineNet = calculateLineTotal(line);
            const lineTax = lineNet * (line.tax_rate / 100);
            subtotal += lineNet;
            taxAmount += lineTax;
        });

        return {
            subtotal,
            taxAmount,
            total: subtotal + taxAmount,
        };
    }, [data.lines, calculateLineTotal]);

    const currencySymbol = useMemo(() => {
        const currency = currencies.find(
            (c) => c.id === Number(data.currency_id),
        );
        return currency?.symbol ?? '$';
    }, [currencies, data.currency_id]);

    // Recalculate Quote Totals based on Items
    useEffect(() => {
        if (data.items.length === 0) return;

        let totalWeight = 0;
        let totalVolume = 0;
        let totalPieces = 0;

        data.items.forEach((item) => {
            item.lines.forEach((line) => {
                totalWeight += Number(line.weight_kg) || 0;
                totalVolume += Number(line.volume_cbm) || 0;
                totalPieces += Number(line.pieces) || 0;
            });
        });

        setData((prev) => ({
            ...prev,
            total_weight_kg: totalWeight,
            total_volume_cbm: totalVolume,
            total_pieces: totalPieces,
        }));
    }, [data.items, setData]);

    // Check for errors in specific tabs
    const hasTabErrors = useCallback(
        (tab: 'general' | 'routing' | 'commodities' | 'financials') => {
            const errorKeys = Object.keys(errors);
            switch (tab) {
                case 'general':
                    return errorKeys.some((k) =>
                        [
                            'customer_id',
                            'shipper_id',
                            'consignee_id',
                            'issuing_company_id',
                            'project_id',
                            'division_id',
                            'valid_until',
                            'carrier_id',
                        ].includes(k),
                    );
                case 'routing':
                    return errorKeys.some((k) =>
                        [
                            'origin_port_id',
                            'destination_port_id',
                            'transport_mode_id',
                            'service_type_id',
                            'transit_days',
                        ].includes(k),
                    );
                case 'commodities':
                    return errorKeys.some((k) => k.startsWith('items'));
                case 'financials':
                    return errorKeys.some(
                        (k) =>
                            k.startsWith('lines') ||
                            [
                                'payment_terms_id',
                                'footer_terms_id',
                                'currency_id',
                            ].includes(k),
                    );
                default:
                    return false;
            }
        },
        [errors],
    );

    // Submit handler
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isEdit && quote) {
            put(`/quotes/${quote.id}`);
        } else {
            post('/quotes');
        }
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="mx-auto max-w-7xl space-y-6 p-6"
        >
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold tracking-tight">
                    {isEdit
                        ? `Editar Cotización ${quote?.quote_number}`
                        : 'Nueva Cotización'}
                </h1>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => window.history.back()}
                    >
                        Cancelar
                    </Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {isEdit
                            ? 'Actualizar Cotización'
                            : 'Guardar Cotización'}
                    </Button>
                </div>
            </div>

            {Object.keys(errors).length > 0 && (
                <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertTitle>Error de Validación</AlertTitle>
                    <AlertDescription>
                        Por favor revise los campos marcados en rojo en las
                        pestañas indicadas.
                    </AlertDescription>
                </Alert>
            )}

            <Tabs defaultValue="general" className="w-full">
                <TabsList className="grid w-full grid-cols-4">
                    <TabsTrigger value="general" className="relative">
                        General
                        {hasTabErrors('general') && (
                            <Badge
                                variant="destructive"
                                className="absolute -top-1 -right-1 h-2 w-2 rounded-full p-0"
                            />
                        )}
                    </TabsTrigger>
                    <TabsTrigger value="routing" className="relative">
                        Ruta y Servicio
                        {hasTabErrors('routing') && (
                            <Badge
                                variant="destructive"
                                className="absolute -top-1 -right-1 h-2 w-2 rounded-full p-0"
                            />
                        )}
                    </TabsTrigger>
                    <TabsTrigger value="commodities" className="relative">
                        Carga (Commodities)
                        {hasTabErrors('commodities') && (
                            <Badge
                                variant="destructive"
                                className="absolute -top-1 -right-1 h-2 w-2 rounded-full p-0"
                            />
                        )}
                    </TabsTrigger>
                    <TabsTrigger value="financials" className="relative">
                        Líneas Financieras
                        {hasTabErrors('financials') && (
                            <Badge
                                variant="destructive"
                                className="absolute -top-1 -right-1 h-2 w-2 rounded-full p-0"
                            />
                        )}
                    </TabsTrigger>
                </TabsList>

                {/* General Tab */}
                <TabsContent value="general" className="mt-6">
                    <QuoteGeneralTab
                        data={data}
                        setData={setData}
                        errors={errors}
                        customers={customers}
                        projects={projects}
                        issuingCompanies={issuingCompanies}
                        carriers={carriers}
                        divisions={divisions}
                    />
                </TabsContent>

                {/* Routing Tab */}
                <TabsContent value="routing" className="mt-6">
                    <QuoteRoutingTab
                        data={data}
                        setData={setData}
                        errors={errors}
                        ports={ports}
                        transportModes={transportModes}
                        serviceTypes={serviceTypes}
                    />
                </TabsContent>

                {/* Commodities Tab */}
                <TabsContent value="commodities" className="mt-6">
                    <QuoteCommoditiesTab
                        data={data}
                        setData={setData}
                        errors={errors}
                    />
                </TabsContent>

                {/* Financials Tab */}
                <TabsContent value="financials" className="mt-6">
                    <QuoteFinancialsTab
                        data={data}
                        setData={setData}
                        errors={errors}
                        productsServices={productsServices}
                        currencies={currencies}
                        paymentTerms={paymentTerms}
                        footerTerms={footerTerms}
                        totals={totals}
                        currencySymbol={currencySymbol}
                        addLine={addLine}
                        removeLine={removeLine}
                        updateLine={updateLine}
                        calculateLineTotal={calculateLineTotal}
                    />
                </TabsContent>
            </Tabs>

            {/* Actions */}
            <div className="flex justify-end gap-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => window.history.back()}
                >
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing}>
                    <Save className="mr-2 h-4 w-4" />
                    {isEdit ? 'Actualizar Cotización' : 'Guardar Cotización'}
                </Button>
            </div>
        </form>
    );
}
