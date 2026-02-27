<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/policy
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_code' => ['nullable', 'string', 'max:100'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.received_qty' => ['required', 'numeric', 'min:0'],
            'lines.*.expected_qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.uom' => ['required', 'string', 'max:20'],
            'lines.*.lot_number' => ['nullable', 'string', 'max:50'],
            'lines.*.serial_number' => ['nullable', 'string', 'max:50'],
            'lines.*.expiration_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'El almacén es requerido.',
            'customer_id.required' => 'El cliente es requerido.',
            'lines.required' => 'Debe agregar al menos una línea.',
            'lines.min' => 'Debe agregar al menos una línea.',
            'lines.*.sku.required' => 'El SKU es requerido para cada línea.',
            'lines.*.received_qty.required' => 'La cantidad recibida es requerida.',
        ];
    }
}
