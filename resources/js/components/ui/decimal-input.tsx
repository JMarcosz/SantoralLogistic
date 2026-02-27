import * as React from "react"
import { Input } from "@/components/ui/input"

interface DecimalInputProps extends Omit<React.ComponentProps<typeof Input>, 'onChange' | 'value'> {
    value: number;
    onChange: (value: number) => void;
    max?: number;
}

const DecimalInput = ({ value, onChange, onBlur, max, ...props }: DecimalInputProps) => {
    const [displayValue, setDisplayValue] = React.useState(value?.toString() || '');

    React.useEffect(() => {
        // Sync with external value changes, but avoid overwriting valid intermediate states
        // e.g. "10." should not be replaced by "10"
        const parsedDisplay = parseFloat(displayValue);
        
        // If the parsed display value matches the prop value, we assume the user is typing
        // and we shouldn't interfere (unless it's a completely different number)
        // But if the prop value changes to something else (e.g. reset form), we must update.
        
        // We use a small epsilon for float comparison if needed, but strict equality is usually fine for form state
        if (parsedDisplay !== value) {
             // Check if it's just a formatting difference (e.g. "10.00" vs 10)
             // If Number(displayValue) != value, then it's a real change.
             // Also handle case where display is empty string (Number('') is 0) but we want to show "0"
             if (Number(displayValue) !== value || (displayValue === '' && value === 0)) {
                 setDisplayValue(value?.toString() || '');
             }
        }
    }, [value]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        let newValue = e.target.value;
        
        // Remove thousands separator commas first (1,000.50 -> 1000.50)
        // Then allow decimal comma to be replaced with dot
        const cleanedValue = newValue.replace(/,(?=\d{3})/g, '').replace(',', '.');

        // Allow empty string, digits, commas (for thousands), and one dot
        if (newValue === '' || /^[\d,]*\.?\d*$/.test(newValue)) {
            setDisplayValue(newValue);
            
            const parsed = parseFloat(cleanedValue);
            if (!isNaN(parsed)) {
                if (max !== undefined && parsed > max) {
                    // If exceeds max, don't update parent? Or update and let parent handle validation?
                    // Usually better to let parent handle validation, but we can clamp if desired.
                    // For now, just pass it up.
                    onChange(parsed);
                } else {
                    onChange(parsed);
                }
            } else {
                onChange(0);
            }
        }
    };

    const handleBlur = (e: React.FocusEvent<HTMLInputElement>) => {
        const parsed = parseFloat(displayValue);
        if (!isNaN(parsed)) {
            setDisplayValue(parsed.toFixed(2));
        } else {
            setDisplayValue('0.00');
        }
        
        if (onBlur) {
            onBlur(e);
        }
    };

    return (
        <Input
            type="text"
            inputMode="decimal"
            value={displayValue}
            onChange={handleChange}
            onBlur={handleBlur}
            {...props}
        />
    );
};

export { DecimalInput }
