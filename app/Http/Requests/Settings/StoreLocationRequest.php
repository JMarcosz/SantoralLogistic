<?php

namespace App\Http\Requests\Settings;

use App\Enums\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations')->where(function ($query) {
                    return $query->where('warehouse_id', $this->warehouse_id);
                }),
            ],
            'zone' => 'nullable|string|max:20',
            'type' => ['required', Rule::enum(LocationType::class)],
            'max_weight_kg' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'El almacén es requerido.',
            'code.required' => 'El código es requerido.',
            'code.unique' => 'Este código ya existe en el almacén seleccionado.',
            'type.required' => 'El tipo es requerido.',
        ];
    }
}
