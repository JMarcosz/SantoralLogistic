<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MovementsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = InventoryMovement::with(['inventoryItem.warehouse', 'inventoryItem.customer', 'fromLocation', 'toLocation', 'user']);

        // Date range
        if (!empty($this->filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($this->filters['date_from'])->startOfDay());
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($this->filters['date_to'])->endOfDay());
        }

        // Warehouse filter (through inventory item)
        if (!empty($this->filters['warehouse_id'])) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('warehouse_id', $this->filters['warehouse_id']));
        }

        // Customer filter
        if (!empty($this->filters['customer_id'])) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('customer_id', $this->filters['customer_id']));
        }

        // SKU filter
        if (!empty($this->filters['sku'])) {
            $query->whereHas('inventoryItem', fn($q) => $q->where('item_code', 'like', "%{$this->filters['sku']}%"));
        }

        // Movement type filter
        if (!empty($this->filters['type'])) {
            $query->where('movement_type', $this->filters['type']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Tipo',
            'Almacén',
            'Cliente',
            'SKU',
            'Descripción',
            'Origen',
            'Destino',
            'Cantidad',
            'Referencia',
            'Usuario',
            'Notas',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->created_at->format('Y-m-d H:i'),
            $movement->movement_type->label(),
            $movement->inventoryItem?->warehouse?->name ?? '',
            $movement->inventoryItem?->customer?->name ?? '',
            $movement->inventoryItem?->item_code ?? '',
            $movement->inventoryItem?->description ?? '',
            $movement->fromLocation?->code ?? '',
            $movement->toLocation?->code ?? '',
            $movement->qty,
            $movement->reference ?? '',
            $movement->user?->name ?? '',
            $movement->notes ?? '',
        ];
    }
}
