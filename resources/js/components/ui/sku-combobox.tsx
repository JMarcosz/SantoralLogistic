import { Input } from '@/components/ui/input';
import { useEffect, useRef, useState } from 'react';

interface SkuResult {
    id: number;
    sku: string;
    name: string;
    description: string;
    uom: string;
}

interface SkuComboboxProps {
    value: string;
    onChange: (sku: string) => void;
    onSelect?: (result: SkuResult) => void;
    placeholder?: string;
    className?: string;
}

export function SkuCombobox({
    value,
    onChange,
    onSelect,
    placeholder = 'Buscar SKU...',
    className,
}: SkuComboboxProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [results, setResults] = useState<SkuResult[]>([]);
    const [loading, setLoading] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<NodeJS.Timeout | null>(null);

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
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const search = async (query: string) => {
        if (query.length < 1) {
            setResults([]);
            return;
        }

        setLoading(true);
        try {
            const response = await fetch(
                `/api/sku-search?q=${encodeURIComponent(query)}`,
            );
            const data = await response.json();
            setResults(data);
            setIsOpen(data.length > 0);
        } catch {
            setResults([]);
        } finally {
            setLoading(false);
        }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const newValue = e.target.value;
        onChange(newValue);

        // Debounce search
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }
        debounceRef.current = setTimeout(() => {
            search(newValue);
        }, 300);
    };

    const handleSelect = (result: SkuResult) => {
        onChange(result.sku);
        onSelect?.(result);
        setIsOpen(false);
        setResults([]);
    };

    return (
        <div ref={containerRef} className="relative">
            <Input
                value={value}
                onChange={handleInputChange}
                onFocus={() => results.length > 0 && setIsOpen(true)}
                placeholder={placeholder}
                className={className}
            />
            {isOpen && results.length > 0 && (
                <div className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-popover p-1 shadow-lg">
                    {loading && (
                        <div className="px-2 py-1 text-sm text-muted-foreground">
                            Buscando...
                        </div>
                    )}
                    {results.map((result) => (
                        <button
                            key={result.id}
                            type="button"
                            onClick={() => handleSelect(result)}
                            className="flex w-full cursor-pointer flex-col items-start rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground"
                        >
                            <span className="font-medium">{result.sku}</span>
                            <span className="text-xs text-muted-foreground">
                                {result.name} • {result.uom}
                            </span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
