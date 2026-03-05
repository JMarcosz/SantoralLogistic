/* eslint-disable @typescript-eslint/no-explicit-any */
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { Currency, ProductService } from '@/types';
import { Check, ChevronsUpDown, Package, Plus, Settings, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { QuoteFormValues } from './quote-form';
import { FormQuoteLine } from './types';

interface Props {
    data: QuoteFormValues;
    setData: (field: keyof QuoteFormValues, value: any) => void;
    errors: Partial<Record<string, string>>;
    productsServices: ProductService[];
    currencies: Currency[];
    paymentTerms: any[];
    footerTerms: any[];
    totals: { subtotal: number; taxAmount: number; total: number };
    currencySymbol: string;
    addLine: (lineType?: 'product' | 'service') => void;
    removeLine: (index: number) => void;
    updateLine: (index: number, field: keyof FormQuoteLine, value: any) => void;
    calculateLineTotal: (line: FormQuoteLine) => number;
}

type LineTab = 'product' | 'service';

/**
 * Searchable combobox for selecting a product or service.
 */
function ProductServiceCombobox({
    value,
    items,
    onChange,
    placeholder = 'Buscar...',
}: {
    value: number | '';
    items: ProductService[];
    onChange: (id: number) => void;
    placeholder?: string;
}) {
    const [open, setOpen] = useState(false);
    const selected = items.find((i) => i.id === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between font-normal"
                >
                    <span className="truncate">
                        {selected ? `${selected.code} — ${selected.name}` : placeholder}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[320px] p-0" align="start">
                <Command>
                    <CommandInput placeholder="Buscar por código o nombre..." />
                    <CommandList>
                        <CommandEmpty>Sin resultados</CommandEmpty>
                        <CommandGroup>
                            {items.map((item) => (
                                <CommandItem
                                    key={item.id}
                                    value={`${item.code} ${item.name}`}
                                    onSelect={() => {
                                        onChange(item.id);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 h-4 w-4',
                                            value === item.id
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                    <div className="flex flex-col">
                                        <span className="font-medium">
                                            {item.code}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            {item.name}
                                        </span>
                                    </div>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

export function QuoteFinancialsTab({
    data,
    setData,
    errors,
    productsServices,
    currencies,
    paymentTerms,
    footerTerms,
    totals,
    currencySymbol,
    addLine,
    removeLine,
    updateLine,
    calculateLineTotal,
}: Props) {
    const [activeLineTab, setActiveLineTab] = useState<LineTab>('service');

    // Split products/services from the full list
    const products = useMemo(
        () => productsServices.filter((p) => p.type === 'product'),
        [productsServices],
    );
    const services = useMemo(
        () => productsServices.filter((p) => p.type === 'service' || p.type === 'fee'),
        [productsServices],
    );

    // Filter lines by active tab
    const filteredLineIndices = useMemo(() => {
        return data.lines
            .map((line, index) => ({ line, index }))
            .filter(({ line }) => (line.line_type || 'service') === activeLineTab);
    }, [data.lines, activeLineTab]);

    const handleAddLine = () => {
        addLine(activeLineTab);
    };

    const activeItems = activeLineTab === 'product' ? products : services;

    return (
        <div className="space-y-6">
            {/* Lines Section */}
            <div className="rounded-lg border bg-card p-6">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-lg font-semibold">
                        Líneas de Cotización
                    </h2>
                </div>

                {/* Product / Service Sub-Tabs */}
                <div className="mb-4 flex items-center gap-1 rounded-lg border bg-muted/30 p-1">
                    <button
                        type="button"
                        onClick={() => setActiveLineTab('product')}
                        className={cn(
                            'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all',
                            activeLineTab === 'product'
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted',
                        )}
                    >
                        <Package className="h-4 w-4" />
                        Productos
                        <span className="ml-1 rounded-full bg-background/20 px-1.5 py-0.5 text-xs">
                            {data.lines.filter((l) => (l.line_type || 'service') === 'product').length}
                        </span>
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveLineTab('service')}
                        className={cn(
                            'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-all',
                            activeLineTab === 'service'
                                ? 'bg-primary text-primary-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted',
                        )}
                    >
                        <Settings className="h-4 w-4" />
                        Servicios
                        <span className="ml-1 rounded-full bg-background/20 px-1.5 py-0.5 text-xs">
                            {data.lines.filter((l) => (l.line_type || 'service') === 'service').length}
                        </span>
                    </button>

                    <div className="ml-auto">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={handleAddLine}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar {activeLineTab === 'product' ? 'Producto' : 'Servicio'}
                        </Button>
                    </div>
                </div>

                {errors.lines && (
                    <p className="mb-4 text-sm text-destructive">
                        {errors.lines}
                    </p>
                )}

                {filteredLineIndices.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-muted-foreground">
                        {activeLineTab === 'product' ? (
                            <Package className="mb-2 h-8 w-8" />
                        ) : (
                            <Settings className="mb-2 h-8 w-8" />
                        )}
                        <p className="text-sm">
                            No hay {activeLineTab === 'product' ? 'productos' : 'servicios'} agregados.
                        </p>
                        <Button
                            type="button"
                            variant="link"
                            size="sm"
                            onClick={handleAddLine}
                            className="mt-2"
                        >
                            <Plus className="mr-1 h-3 w-3" />
                            Agregar {activeLineTab === 'product' ? 'un producto' : 'un servicio'}
                        </Button>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[280px]">
                                        {activeLineTab === 'product' ? 'Producto' : 'Servicio'} *
                                    </TableHead>
                                    <TableHead className="w-[200px]">
                                        Descripción
                                    </TableHead>
                                    <TableHead className="w-[100px]">
                                        Cantidad *
                                    </TableHead>
                                    <TableHead className="w-[120px]">
                                        Precio Unit. *
                                    </TableHead>
                                    <TableHead className="w-[80px]">
                                        Desc. %
                                    </TableHead>
                                    <TableHead className="w-[80px]">
                                        ITBIS %
                                    </TableHead>
                                    <TableHead className="w-[120px] text-right">
                                        Total
                                    </TableHead>
                                    <TableHead className="w-[60px]"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredLineIndices.map(({ line, index }) => (
                                    <TableRow key={index}>
                                        <TableCell>
                                            <ProductServiceCombobox
                                                value={line.product_service_id}
                                                items={activeItems}
                                                onChange={(id) =>
                                                    updateLine(
                                                        index,
                                                        'product_service_id',
                                                        id,
                                                    )
                                                }
                                                placeholder={
                                                    activeLineTab === 'product'
                                                        ? 'Seleccionar producto...'
                                                        : 'Seleccionar servicio...'
                                                }
                                            />
                                            {errors[
                                                `lines.${index}.product_service_id`
                                            ] && (
                                                    <p className="text-xs text-destructive">
                                                        {
                                                            errors[
                                                            `lines.${index}.product_service_id`
                                                            ]
                                                        }
                                                    </p>
                                                )}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={line.description}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'description',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Descripción"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                min="0.0001"
                                                step="0.0001"
                                                value={line.quantity}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'quantity',
                                                        parseFloat(
                                                            e.target.value,
                                                        ) || 0,
                                                    )
                                                }
                                            />
                                            {errors[`lines.${index}.quantity`] && (
                                                <p className="text-xs text-destructive">
                                                    {
                                                        errors[
                                                        `lines.${index}.quantity`
                                                        ]
                                                    }
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={line.unit_price}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'unit_price',
                                                        parseFloat(
                                                            e.target.value,
                                                        ) || 0,
                                                    )
                                                }
                                            />
                                            {errors[
                                                `lines.${index}.unit_price`
                                            ] && (
                                                    <p className="text-xs text-destructive">
                                                        {
                                                            errors[
                                                            `lines.${index}.unit_price`
                                                            ]
                                                        }
                                                    </p>
                                                )}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={line.discount_percent}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'discount_percent',
                                                        parseFloat(
                                                            e.target.value,
                                                        ) || 0,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={line.tax_rate}
                                                onChange={(e) =>
                                                    updateLine(
                                                        index,
                                                        'tax_rate',
                                                        parseFloat(
                                                            e.target.value,
                                                        ) || 0,
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="text-right font-mono font-semibold">
                                            {currencySymbol}
                                            {calculateLineTotal(
                                                line,
                                            ).toLocaleString('en-US', {
                                                minimumFractionDigits: 2,
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => removeLine(index)}
                                                disabled={data.lines.length <= 1}
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}

                {/* Totals */}
                <div className="mt-6 flex justify-end">
                    <div className="w-64 space-y-2 rounded-lg border bg-muted/50 p-4">
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">
                                Subtotal:
                            </span>
                            <span className="font-mono">
                                {currencySymbol}
                                {totals.subtotal.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                })}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">
                                ITBIS:
                            </span>
                            <span className="font-mono">
                                {currencySymbol}
                                {totals.taxAmount.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                })}
                            </span>
                        </div>
                        <div className="border-t pt-2">
                            <div className="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span className="font-mono text-primary">
                                    {currencySymbol}
                                    {totals.total.toLocaleString('en-US', {
                                        minimumFractionDigits: 2,
                                    })}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Notes & Terms */}
            <div className="rounded-lg border bg-card p-6">
                <h2 className="mb-4 text-lg font-semibold">Notas y Términos</h2>

                <div className="grid gap-4 md:grid-cols-2">
                    {/* Incoterms (New) */}
                    <div className="space-y-2">
                        <Label>Incoterms</Label>
                        <Select
                            value={data.incoterms || ''}
                            onValueChange={(v) => setData('incoterms', v)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar" />
                            </SelectTrigger>
                            <SelectContent>
                                {[
                                    'EXW',
                                    'FCA',
                                    'CPT',
                                    'CIP',
                                    'DAP',
                                    'DPU',
                                    'DDP',
                                    'FAS',
                                    'FOB',
                                    'CFR',
                                    'CIF',
                                ].map((t) => (
                                    <SelectItem key={t} value={t}>
                                        {t}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Currency */}
                    <div className="space-y-2">
                        <Label>Moneda *</Label>
                        <Select
                            value={String(data.currency_id)}
                            onValueChange={(v) =>
                                setData('currency_id', Number(v))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar moneda" />
                            </SelectTrigger>
                            <SelectContent>
                                {currencies.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.code} ({c.symbol})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.currency_id && (
                            <p className="text-sm text-destructive">
                                {errors.currency_id}
                            </p>
                        )}
                    </div>

                    {/* Payment Terms */}
                    <div className="space-y-2">
                        <Label>Términos de Pago</Label>
                        <Select
                            value={String(data.payment_terms_id || '')}
                            onValueChange={(v) =>
                                setData('payment_terms_id', v ? Number(v) : '')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Usar por defecto" />
                            </SelectTrigger>
                            <SelectContent>
                                {paymentTerms.map((t) => (
                                    <SelectItem key={t.id} value={String(t.id)}>
                                        {t.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Footer Terms */}
                    <div className="space-y-2">
                        <Label>Términos de Cotización</Label>
                        <Select
                            value={String(data.footer_terms_id || '')}
                            onValueChange={(v) =>
                                setData('footer_terms_id', v ? Number(v) : '')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Usar por defecto" />
                            </SelectTrigger>
                            <SelectContent>
                                {footerTerms.map((t) => (
                                    <SelectItem key={t.id} value={String(t.id)}>
                                        {t.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Notes */}
                    <div className="space-y-2 md:col-span-2">
                        <Label>Notas Internas</Label>
                        <Textarea
                            rows={3}
                            value={data.notes || ''}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Notas internas (no visibles para el cliente)"
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
