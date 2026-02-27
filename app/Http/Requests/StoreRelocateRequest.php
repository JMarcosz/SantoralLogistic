<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRelocateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('relocate', $this->route('item'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $item = $this->route('item');
        $maxQty = $item ? $item->qty : 0;

        return [
            'to_location_id' => [
                'required',
                'integer',
                'exists:locations,id',
                // Ensure location belongs to the same warehouse as the item
                function ($attribute, $value, $fail) use ($item) {
                    $location = \App\Models\Location::find($value);
                    if ($location && $item && $location->warehouse_id !== $item->warehouse_id) {
                        $fail('La ubicación destino debe pertenecer al mismo almacén que el ítem.');
                    }
                },
                // Ensure destination is different from current location
                function ($attribute, $value, $fail) use ($item) {
                    if ($item && (int) $value === $item->location_id) {
                        $fail('La ubicación destino no puede ser la misma que la ubicación actual.');
                    }
                },
            ],
            'qty' => [
                'required',
                'numeric',
                'min:0.001',
                "max:{$maxQty}",
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'to_location_id.required' => 'La ubicación destino es requerida.',
            'to_location_id.exists' => 'La ubicación destino seleccionada no existe.',
            'qty.required' => 'La cantidad es requerida.',
            'qty.min' => 'La cantidad debe ser mayor a 0.',
            'qty.max' => 'La cantidad no puede exceder la cantidad disponible.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'to_location_id' => 'ubicación destino',
            'qty' => 'cantidad',
            'notes' => 'notas',
        ];
    }
}
