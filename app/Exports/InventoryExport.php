<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InventoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = InventoryItem::with(['warehouse', 'customer', 'location'])
            ->where('qty', '>', 0)
            ->orderBy('customer_id')
            ->orderBy('item_code');

        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['sku'])) {
            $query->where('item_code', 'like', "%{$this->filters['sku']}%");
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Almacén',
            'Ubicación',
            'SKU',
            'Descripción',
            'Cantidad',
            'UOM',
            'Lote',
            'Serial',
            'Vencimiento',
            'Recibido En',
        ];
    }

    public function map($item): array
    {
        return [
            $item->customer?->name ?? '',
            $item->warehouse?->name ?? '',
            $item->location?->code ?? 'Sin ubicar',
            $item->item_code,
            $item->description ?? '',
            $item->qty,
            $item->uom,
            $item->lot_number ?? '',
            $item->serial_number ?? '',
            $item->expiration_date?->format('Y-m-d') ?? '',
            $item->received_at?->format('Y-m-d H:i') ?? '',
        ];
    }
}
