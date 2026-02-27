import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { Plus, Trash2 } from 'lucide-react';

export interface ContainerDetail {
    container_number: string;
    container_type: string;
    seal_number: string;
    weight_kg: string;
}

const CONTAINER_TYPES = [
    { value: '20GP', label: "20' General Purpose" },
    { value: '40GP', label: "40' General Purpose" },
    { value: '40HC', label: "40' High Cube" },
    { value: '20RF', label: "20' Reefer" },
    { value: '40RF', label: "40' Reefer" },
    { value: '20OT', label: "20' Open Top" },
    { value: '40OT', label: "40' Open Top" },
    { value: '20FR', label: "20' Flat Rack" },
    { value: '40FR', label: "40' Flat Rack" },
];

interface ContainerDetailsTableProps {
    containers: ContainerDetail[];
    onChange: (containers: ContainerDetail[]) => void;
}

export function ContainerDetailsTable({
    containers,
    onChange,
}: ContainerDetailsTableProps) {
    const addContainer = () => {
        onChange([
            ...containers,
            {
                container_number: '',
                container_type: '40HC',
                seal_number: '',
                weight_kg: '',
            },
        ]);
    };

    const removeContainer = (index: number) => {
        onChange(containers.filter((_, i) => i !== index));
    };

    const updateContainer = (
        index: number,
        field: keyof ContainerDetail,
        value: string,
    ) => {
        const updated = containers.map((container, i) =>
            i === index ? { ...container, [field]: value } : container,
        );
        onChange(updated);
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <Label className="text-base font-medium">Contenedores</Label>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addContainer}
                    className="gap-1"
                >
                    <Plus className="h-4 w-4" />
                    Agregar
                </Button>
            </div>

            {containers.length > 0 ? (
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-[180px]">
                                    Número
                                </TableHead>
                                <TableHead className="w-[180px]">
                                    Tipo
                                </TableHead>
                                <TableHead className="w-[140px]">
                                    Sello
                                </TableHead>
                                <TableHead className="w-[120px]">
                                    Peso (kg)
                                </TableHead>
                                <TableHead className="w-[60px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {containers.map((container, index) => (
                                <TableRow key={index}>
                                    <TableCell className="p-2">
                                        <Input
                                            value={container.container_number}
                                            onChange={(e) =>
                                                updateContainer(
                                                    index,
                                                    'container_number',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="MSKU1234567"
                                            className="h-9"
                                        />
                                    </TableCell>
                                    <TableCell className="p-2">
                                        <Select
                                            value={container.container_type}
                                            onValueChange={(value) =>
                                                updateContainer(
                                                    index,
                                                    'container_type',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-9">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {CONTAINER_TYPES.map((type) => (
                                                    <SelectItem
                                                        key={type.value}
                                                        value={type.value}
                                                    >
                                                        {type.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </TableCell>
                                    <TableCell className="p-2">
                                        <Input
                                            value={container.seal_number}
                                            onChange={(e) =>
                                                updateContainer(
                                                    index,
                                                    'seal_number',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="Sello"
                                            className="h-9"
                                        />
                                    </TableCell>
                                    <TableCell className="p-2">
                                        <Input
                                            type="number"
                                            value={container.weight_kg}
                                            onChange={(e) =>
                                                updateContainer(
                                                    index,
                                                    'weight_kg',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="0"
                                            className="h-9"
                                        />
                                    </TableCell>
                                    <TableCell className="p-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() =>
                                                removeContainer(index)
                                            }
                                            className="h-8 w-8 text-destructive hover:bg-destructive/10"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            ) : (
                <div className="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
                    <p>No hay contenedores agregados</p>
                    <p className="text-sm">
                        Haga clic en "Agregar" para añadir un contenedor
                    </p>
                </div>
            )}
        </div>
    );
}
