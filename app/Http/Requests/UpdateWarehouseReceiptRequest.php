<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => 'nullable|string|max:100',
            'expected_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'sometimes|array|min:1',
            'lines.*.id' => 'nullable|integer|exists:warehouse_receipt_lines,id',
            'lines.*.sku' => 'required|string|max:100',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.expected_qty' => 'nullable|numeric|min:0',
            'lines.*.received_qty' => 'required|numeric|min:0',
            'lines.*.uom' => 'required|string|max:20',
            'lines.*.lot_number' => 'nullable|string|max:100',
            'lines.*.serial_number' => 'nullable|string|max:100',
            'lines.*.expiration_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.*.sku.required' => 'El SKU es requerido para cada línea.',
            'lines.*.received_qty.required' => 'La cantidad recibida es requerida.',
        ];
    }
}
