import { Input } from '@/components/ui/input';
import { useEffect, useRef, useState } from 'react';
import axios from 'axios';

export interface CustomerItemResult {
    id: number;
    code: string | null;
    description: string;
    uom: string;
}

interface CustomerItemComboboxProps {
    customerId?: number | null;
    value: string;
    onChange: (value: string) => void;
    onSelect?: (result: CustomerItemResult) => void;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
}

export function CustomerItemCombobox({
    customerId,
    value,
    onChange,
    onSelect,
    placeholder = 'Search item...',
    className,
    disabled = false,
}: CustomerItemComboboxProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [results, setResults] = useState<CustomerItemResult[]>([]);
    const [loading, setLoading] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<NodeJS.Timeout | null>(null);

    // Disable if no customer selected
    const isDisabled = disabled || !customerId;
    const effectivePlaceholder = !customerId
        ? 'Select a customer first'
        : placeholder;

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (
                containerRef.current &&
                !containerRef.current.contains(e.target as Node)
            ) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Clear results when customer changes
    useEffect(() => {
        setResults([]);
        setIsOpen(false);
    }, [customerId]);

    const search = async (query: string) => {
        if (!customerId) return;
        if (query.length < 1) {
            setResults([]);
            return;
        }

        setLoading(true);
        try {
            const response = await axios.get('/api/customer-items', {
                params: {
                    customer_id: customerId,
                    q: query,
                },
            });
            setResults(response.data);
            setIsOpen(response.data.length > 0);
        } catch (error) {
            console.error('Failed to search customer items:', error);
            setResults([]);
        } finally {
            setLoading(false);
        }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newValue = e.target.value;
        onChange(newValue);

        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        if (!customerId) return;

        debounceRef.current = setTimeout(() => {
            search(newValue);
        }, 300);
    };

    const handleSelect = (result: CustomerItemResult) => {
        // If code exists, use it? Or use description?
        // Requirement says: "Se puede crear una recepción sin SKU/code"
        // But usually combobox value is the text displayed.
        // We will pass the code if exists, otherwise description (?)
        // Actually the prop is 'value' (string). It typically maps to the input text.
        // But the user might want to see the Code in the input if selected.
        const displayValue = result.code || result.description;
        onChange(displayValue);
        onSelect?.(result);
        setIsOpen(false);
        setResults([]);
    };

    return (
        <div ref={containerRef} className="relative">
            <Input
                value={value}
                onChange={handleInputChange}
                onFocus={() =>
                    !isDisabled && results.length > 0 && setIsOpen(true)
                }
                placeholder={effectivePlaceholder}
                className={className}
                disabled={isDisabled}
            />
            {isOpen && results.length > 0 && (
                <div className="bg-popover text-popover-foreground absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border p-1 shadow-lg">
                    {loading && (
                        <div className="text-muted-foreground px-2 py-1 text-sm">
                            Searching...
                        </div>
                    )}
                    {results.map((result) => (
                        <button
                            key={result.id}
                            type="button"
                            onClick={() => handleSelect(result)}
                            className="hover:bg-accent hover:text-accent-foreground flex w-full cursor-pointer flex-col items-start rounded-sm px-2 py-1.5 text-left text-sm"
                        >
                            <span className="font-medium">
                                {result.code || 'No Code'}
                            </span>
                            <span className="text-muted-foreground text-xs">
                                {result.description} • {result.uom}
                            </span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
