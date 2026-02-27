/* eslint-disable @typescript-eslint/no-explicit-any */
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { QuoteItem, QuoteItemLine } from '@/types';
import { Box, Container, Plus, Trash, Truck } from 'lucide-react';
import { QuoteFormValues } from './quote-form';

interface Props {
    data: QuoteFormValues;
    setData: (field: keyof QuoteFormValues, value: any) => void;
    errors: Partial<Record<string, string>>;
}

export function QuoteCommoditiesTab({ data, setData, errors }: Props) {
    const items = (data.items || []) as QuoteItem[];

    const addItem = (type: QuoteItem['type']) => {
        const newItem: QuoteItem = {
            type,
            identifier: '',
            seal_number: '',
            properties: {},
            lines: [
                {
                    pieces: 1,
                    description: '',
                    weight_kg: 0,
                    volume_cbm: 0,
                },
            ],
        };
        setData('items', [...items, newItem]);
    };

    const removeItem = (index: number) => {
        const newItems = items.filter((_, i) => i !== index);
        setData('items', newItems);
    };

    const updateItem = (index: number, field: keyof QuoteItem, value: any) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [field]: value };
        setData('items', newItems);
    };

    const updateItemLine = (
        itemIndex: number,
        lineIndex: number,
        field: keyof QuoteItemLine,
        value: any,
    ) => {
        const newItems = [...items];
        const newLines = [...newItems[itemIndex].lines];
        newLines[lineIndex] = { ...newLines[lineIndex], [field]: value };
        newItems[itemIndex].lines = newLines;
        setData('items', newItems);
    };

    const addItemLine = (itemIndex: number) => {
        const newItems = [...items];
        newItems[itemIndex].lines.push({
            pieces: 1,
            description: '',
            weight_kg: 0,
            volume_cbm: 0,
        });
        setData('items', newItems);
    };

    const removeItemLine = (itemIndex: number, lineIndex: number) => {
        const newItems = [...items];
        if (newItems[itemIndex].lines.length > 1) {
            newItems[itemIndex].lines = newItems[itemIndex].lines.filter(
                (_, i) => i !== lineIndex,
            );
            setData('items', newItems);
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex gap-4">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => addItem('container')}
                >
                    <Container className="mr-2 h-4 w-4" />
                    Agregar Contenedor
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => addItem('vehicle')}
                >
                    <Truck className="mr-2 h-4 w-4" />
                    Agregar Vehículo
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => addItem('loose_cargo')}
                >
                    <Box className="mr-2 h-4 w-4" />
                    Carga Suelta
                </Button>
            </div>

            {items.map((item, index) => (
                <Card key={index} className="relative">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="absolute top-2 right-2 text-destructive"
                        onClick={() => removeItem(index)}
                    >
                        <Trash className="h-4 w-4" />
                    </Button>

                    <CardContent className="pt-6 text-sm">
                        <div className="mb-4 grid gap-4 md:grid-cols-3">
                            <div>
                                <Label>Tipo</Label>
                                <div className="flex items-center gap-2 font-medium capitalize">
                                    {item.type === 'container' && (
                                        <Container className="h-4 w-4" />
                                    )}
                                    {item.type === 'vehicle' && (
                                        <Truck className="h-4 w-4" />
                                    )}
                                    {item.type === 'loose_cargo' && (
                                        <Box className="h-4 w-4" />
                                    )}
                                    {item.type.replace('_', ' ')}
                                </div>
                            </div>
                            <div>
                                <Label>Identificador (ID/VIN)</Label>
                                <Input
                                    value={item.identifier}
                                    onChange={(e) =>
                                        updateItem(
                                            index,
                                            'identifier',
                                            e.target.value,
                                        )
                                    }
                                    placeholder={
                                        item.type === 'vehicle' ? 'VIN' : 'ID'
                                    }
                                />
                                {errors?.[`items.${index}.identifier`] && (
                                    <p className="text-xs text-destructive">
                                        {errors[`items.${index}.identifier`]}
                                    </p>
                                )}
                            </div>
                            {item.type === 'container' && (
                                <div>
                                    <Label>Precinto (Seal)</Label>
                                    <Input
                                        value={item.seal_number}
                                        onChange={(e) =>
                                            updateItem(
                                                index,
                                                'seal_number',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Seal Number"
                                    />
                                    {errors?.[`items.${index}.seal_number`] && (
                                        <p className="text-xs text-destructive">
                                            {
                                                errors[
                                                    `items.${index}.seal_number`
                                                ]
                                            }
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Nested Lines Table */}
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[80px]">
                                            Piezas
                                        </TableHead>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead className="w-[100px]">
                                            Peso (kg)
                                        </TableHead>
                                        <TableHead className="w-[100px]">
                                            Vol (CBM)
                                        </TableHead>
                                        <TableHead className="w-[50px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {item.lines.map((line, lineIndex) => (
                                        <TableRow key={lineIndex}>
                                            <TableCell>
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    value={line.pieces}
                                                    onChange={(e) =>
                                                        updateItemLine(
                                                            index,
                                                            lineIndex,
                                                            'pieces',
                                                            Number(
                                                                e.target.value,
                                                            ),
                                                        )
                                                    }
                                                    className="h-8"
                                                />
                                                {errors?.[
                                                    `items.${index}.lines.${lineIndex}.pieces`
                                                ] && (
                                                    <p className="text-[10px] text-destructive">
                                                        {
                                                            errors[
                                                                `items.${index}.lines.${lineIndex}.pieces`
                                                            ]
                                                        }
                                                    </p>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Input
                                                    value={line.description}
                                                    onChange={(e) =>
                                                        updateItemLine(
                                                            index,
                                                            lineIndex,
                                                            'description',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="h-8"
                                                />
                                                {errors?.[
                                                    `items.${index}.lines.${lineIndex}.description`
                                                ] && (
                                                    <p className="text-[10px] text-destructive">
                                                        {
                                                            errors[
                                                                `items.${index}.lines.${lineIndex}.description`
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
                                                    value={line.weight_kg}
                                                    onChange={(e) =>
                                                        updateItemLine(
                                                            index,
                                                            lineIndex,
                                                            'weight_kg',
                                                            Number(
                                                                e.target.value,
                                                            ),
                                                        )
                                                    }
                                                    className="h-8"
                                                />
                                                {errors?.[
                                                    `items.${index}.lines.${lineIndex}.weight_kg`
                                                ] && (
                                                    <p className="text-[10px] text-destructive">
                                                        {
                                                            errors[
                                                                `items.${index}.lines.${lineIndex}.weight_kg`
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
                                                    value={line.volume_cbm}
                                                    onChange={(e) =>
                                                        updateItemLine(
                                                            index,
                                                            lineIndex,
                                                            'volume_cbm',
                                                            Number(
                                                                e.target.value,
                                                            ),
                                                        )
                                                    }
                                                    className="h-8"
                                                />
                                                {errors?.[
                                                    `items.${index}.lines.${lineIndex}.volume_cbm`
                                                ] && (
                                                    <p className="text-[10px] text-destructive">
                                                        {
                                                            errors[
                                                                `items.${index}.lines.${lineIndex}.volume_cbm`
                                                            ]
                                                        }
                                                    </p>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        removeItemLine(
                                                            index,
                                                            lineIndex,
                                                        )
                                                    }
                                                >
                                                    <Trash className="h-3 w-3" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            <div className="bg-muted/50 p-2">
                                <Button
                                    type="button"
                                    variant="link"
                                    size="sm"
                                    className="h-auto p-0"
                                    onClick={() => addItemLine(index)}
                                >
                                    <Plus className="mr-2 h-3 w-3" />
                                    Agregar Línea de Carga
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            ))}

            {items.length === 0 && (
                <div className="flex h-32 items-center justify-center rounded-lg border border-dashed text-muted-foreground">
                    No hay items de carga. Agregue uno arriba.
                </div>
            )}
        </div>
    );
}
