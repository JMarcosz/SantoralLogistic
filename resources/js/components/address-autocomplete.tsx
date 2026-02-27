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
import { Check, ChevronsUpDown, MapPin } from 'lucide-react';
import { useEffect, useState } from 'react';

interface AddressResult {
    id: string;
    source: string;
    address: string;
    city: string;
    country: string;
}

interface Props {
    value?: string;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
}

export function AddressAutocomplete({
    value,
    onChange,
    placeholder = 'Buscar o ingresar dirección...',
    disabled = false,
}: Props) {
    const [open, setOpen] = useState(false);
    const [inputValue, setInputValue] = useState(value || '');
    const [results, setResults] = useState<AddressResult[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setInputValue(value || '');
    }, [value]);

    useEffect(() => {
        const fetchAddresses = async () => {
            if (inputValue.length < 3) {
                setResults([]);
                return;
            }

            setLoading(true);
            try {
                const response = await fetch(
                    `/api/address-search?query=${encodeURIComponent(inputValue)}`,
                );
                const data = (await response.json()) as AddressResult[];
                setResults(data);
            } catch (error) {
                console.error('Error fetching addresses:', error);
            } finally {
                setLoading(false);
            }
        };

        const timer = setTimeout(fetchAddresses, 500);
        return () => clearTimeout(timer);
    }, [inputValue]);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'h-auto min-h-[2.5rem] w-full justify-between py-2 text-left whitespace-normal',
                        !value && 'text-muted-foreground',
                    )}
                    disabled={disabled}
                >
                    {value ? (
                        <span className="line-clamp-2">{value}</span>
                    ) : (
                        placeholder
                    )}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[400px] p-0" align="start">
                <Command shouldFilter={false}>
                    <CommandInput
                        placeholder="Escribe para buscar..."
                        value={inputValue}
                        onValueChange={(val: string) => {
                            setInputValue(val);
                            onChange(val); // Allow free text typing
                        }}
                    />
                    <CommandList>
                        {loading && (
                            <div className="py-6 text-center text-sm text-muted-foreground">
                                Buscando...
                            </div>
                        )}
                        {!loading && results.length === 0 && (
                            <CommandEmpty className="py-6 text-center text-sm text-muted-foreground">
                                No se encontraron sugerencias. Puedes escribir
                                una nueva dirección.
                            </CommandEmpty>
                        )}
                        {results.length > 0 && (
                            <CommandGroup heading="Sugerencias">
                                {results.map((result) => (
                                    <CommandItem
                                        key={result.id}
                                        value={result.address}
                                        onSelect={() => {
                                            onChange(result.address);
                                            setOpen(false);
                                        }}
                                    >
                                        <Check
                                            className={cn(
                                                'mr-2 h-4 w-4',
                                                value === result.address
                                                    ? 'opacity-100'
                                                    : 'opacity-0',
                                            )}
                                        />
                                        <div className="flex flex-col">
                                            <span className="font-medium">
                                                {result.address}
                                            </span>
                                            <span className="mt-1 flex items-center text-xs text-muted-foreground">
                                                <MapPin className="mr-1 h-3 w-3" />
                                                {result.city}, {result.country}{' '}
                                                ({result.source})
                                            </span>
                                        </div>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
