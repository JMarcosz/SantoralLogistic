<?php

namespace App\Http\Requests;

use App\Enums\ShippingOrderStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $shippingOrder = $this->route('shippingOrder');

        return $shippingOrder
            && $this->user()->can('reserveInventory', $shippingOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sku' => ['required', 'string', 'max:100'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Debe especificar al menos una línea de reserva.',
            'lines.min' => 'Debe especificar al menos una línea de reserva.',
            'lines.*.sku.required' => 'El SKU es requerido en cada línea.',
            'lines.*.qty.required' => 'La cantidad es requerida en cada línea.',
            'lines.*.qty.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'lines' => 'líneas de reserva',
            'lines.*.sku' => 'SKU',
            'lines.*.qty' => 'cantidad',
            'lines.*.warehouse_id' => 'almacén',
        ];
    }
}
