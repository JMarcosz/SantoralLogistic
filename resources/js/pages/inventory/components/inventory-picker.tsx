import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { useEffect, useState, useTransition } from 'react';
import { useDebounce } from 'use-debounce';

interface InventoryItem {
    sku: string;
    description: string;
    available: number;
    uom: string | null;
}

interface Props {
    customerId: number;
    value?: string;
    onChange: (value: string) => void;
    onSelect?: (item: InventoryItem) => void;
    placeholder?: string;
    className?: string;
}

export function InventoryPicker({
    customerId,
    value,
    onChange,
    onSelect,
    placeholder = 'Buscar producto...',
    className,
}: Props) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [debouncedQuery] = useDebounce(query, 300);
    const [isPending, startTransition] = useTransition();
    const [items, setItems] = useState<InventoryItem[]>([]);

    useEffect(() => {
        if (!open) return;

        const controller = new AbortController();

        startTransition(() => {
            axios
                .get('/api/inventory/search', {
                    params: {
                        query: debouncedQuery,
                        customer_id: customerId,
                    },
                    signal: controller.signal,
                })
                .then((res) => {
                    setItems(res.data);
                })
                .catch((err) => {
                    if (err.name !== 'CanceledError') {
                        console.error('Error searching inventory:', err);
                        setItems([]);
                    }
                });
        });

        return () => controller.abort();
    }, [debouncedQuery, customerId, open]);

    const selectedItem = items.find((item) => item.sku === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'w-full justify-between font-normal',
                        className,
                    )}
                >
                    {value ? (
                        <span className="truncate">
                            <span className="font-mono font-semibold">
                                {value}
                            </span>
                            {selectedItem && (
                                <span className="ml-2 text-muted-foreground">
                                    - {selectedItem.description}
                                </span>
                            )}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">
                            {placeholder}
                        </span>
                    )}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[400px] p-0" align="start">
                <Command shouldFilter={false}>
                    <CommandInput
                        placeholder="Buscar por SKU o descripción..."
                        value={query}
                        onValueChange={setQuery}
                    />
                    <CommandList>
                        {isPending ? (
                            <div className="flex items-center justify-center p-4 text-sm text-muted-foreground">
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Buscando...
                            </div>
                        ) : (
                            <>
                                {items.length === 0 && (
                                    <CommandEmpty>
                                        No se encontraron productos.
                                    </CommandEmpty>
                                )}
                                <CommandGroup>
                                    {items.map((item) => (
                                        <CommandItem
                                            key={item.sku}
                                            value={item.sku}
                                            onSelect={() => {
                                                onChange(item.sku);
                                                onSelect?.(item);
                                                setOpen(false);
                                            }}
                                        >
                                            <Check
                                                className={cn(
                                                    'mr-2 h-4 w-4',
                                                    value === item.sku
                                                        ? 'opacity-100'
                                                        : 'opacity-0',
                                                )}
                                            />
                                            <div className="flex flex-1 flex-col overflow-hidden">
                                                <div className="flex justify-between">
                                                    <span className="font-mono font-semibold">
                                                        {item.sku}
                                                    </span>
                                                    <span
                                                        className={cn(
                                                            'text-xs font-medium',
                                                            item.available > 0
                                                                ? 'text-emerald-500'
                                                                : 'text-red-500',
                                                        )}
                                                    >
                                                        Disp: {item.available}{' '}
                                                        {item.uom}
                                                    </span>
                                                </div>
                                                <span className="truncate text-xs text-muted-foreground">
                                                    {item.description}
                                                </span>
                                            </div>
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            </>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
