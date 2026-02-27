<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('package_types.update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('package_types', 'code')->ignore($this->route('package_type')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', 'string', Rule::in(['box', 'pallet', 'container', 'envelope', 'other'])],
            'length_cm' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'width_cm' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'max_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'is_container' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'category' => 'categoría',
            'length_cm' => 'largo',
            'width_cm' => 'ancho',
            'height_cm' => 'alto',
            'max_weight_kg' => 'peso máximo',
            'is_container' => 'es contenedor',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Este código ya existe.',
            'name.required' => 'El nombre es obligatorio.',
            'category.in' => 'La categoría debe ser: box, pallet, container, envelope u other.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper($this->code)]);
        }

        foreach (['is_container', 'is_active'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        // Convert empty strings to null for numeric fields
        foreach (['length_cm', 'width_cm', 'height_cm', 'max_weight_kg'] as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
