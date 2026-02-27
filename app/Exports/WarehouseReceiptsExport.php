<?php

namespace App\Exports;

use App\Models\WarehouseReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class WarehouseReceiptsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = WarehouseReceipt::with(['warehouse', 'customer'])
            ->withCount('lines')
            ->withSum('lines', 'received_qty')
            ->orderBy('created_at', 'desc');

        if (!empty($this->filters['warehouse_id'])) {
            $query->where('warehouse_id', $this->filters['warehouse_id']);
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Número',
            'Almacén',
            'Cliente',
            'Referencia',
            'Estado',
            'Esperado',
            'Recibido',
            'Líneas',
            'Total Recibido',
            'Creado',
        ];
    }

    public function map($receipt): array
    {
        return [
            $receipt->receipt_number ?? "#{$receipt->id}",
            $receipt->warehouse?->name ?? '',
            $receipt->customer?->name ?? '',
            $receipt->reference ?? '',
            $receipt->status->label(),
            $receipt->expected_at?->format('Y-m-d') ?? '',
            $receipt->received_at?->format('Y-m-d') ?? '',
            $receipt->lines_count ?? 0,
            $receipt->lines_sum_received_qty ?? 0,
            $receipt->created_at?->format('Y-m-d H:i') ?? '',
        ];
    }
}
