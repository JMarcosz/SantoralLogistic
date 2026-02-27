<?php

namespace App\Http\Requests\Settings;

use App\Enums\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $location = $this->route('location');

        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations')->where(function ($query) {
                    return $query->where('warehouse_id', $this->warehouse_id);
                })->ignore($location),
            ],
            'zone' => 'nullable|string|max:20',
            'type' => ['required', Rule::enum(LocationType::class)],
            'max_weight_kg' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}
