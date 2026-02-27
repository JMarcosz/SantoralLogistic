<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products_services.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:products_services,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'string', Rule::in(['service', 'product', 'fee'])],
            'uom' => ['nullable', 'string', 'max:30'],
            'default_currency_id' => ['nullable', 'exists:currencies,id'],
            'default_unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999999.9999'],
            'taxable' => ['boolean'],
            'gl_account_code' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'type' => 'tipo',
            'uom' => 'unidad de medida',
            'default_currency_id' => 'moneda por defecto',
            'default_unit_price' => 'precio unitario',
            'taxable' => 'gravable',
            'gl_account_code' => 'cuenta contable',
            'is_active' => 'estado activo',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Este código ya existe.',
            'name.required' => 'El nombre es obligatorio.',
            'type.required' => 'El tipo es obligatorio.',
            'type.in' => 'El tipo debe ser: service, product o fee.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper($this->code)]);
        }

        foreach (['taxable', 'is_active'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        if ($this->has('default_unit_price') && $this->default_unit_price === '') {
            $this->merge(['default_unit_price' => null]);
        }

        if ($this->has('default_currency_id') && $this->default_currency_id === '') {
            $this->merge(['default_currency_id' => null]);
        }
    }
}
