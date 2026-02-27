<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customers.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:30', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:200'],
            'tax_id' => ['nullable', 'string', 'max:20'], // Actualizacion de regla
            'tax_id_type' => ['nullable', 'in:RNC,CEDULA,OTHER'], // Nuevos campos
            'fiscal_name' => ['nullable', 'string', 'max:255'], // Nuevos campos
            'ncf_type_default' => ['nullable', 'in:B01,B02,B14'], // Nuevos campos
            'billing_address' => ['nullable', 'string', 'max:2000'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'email_billing' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'string', Rule::in(['prospect', 'active', 'inactive'])],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency_id' => ['nullable', 'exists:currencies,id'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'tax_id' => 'RNC/NIF',
            'tax_id_type' => 'tipo de identificación fiscal',
            'fiscal_name' => 'nombre fiscal',
            'ncf_type_default' => 'tipo de NCF por defecto',
            'series' => 'serie de NCF',
            'billing_address' => 'dirección de facturación',
            'shipping_address' => 'dirección de envío',
            'city' => 'ciudad',
            'state' => 'estado/provincia',
            'country' => 'país',
            'email_billing' => 'email de facturación',
            'phone' => 'teléfono',
            'website' => 'sitio web',
            'status' => 'estado',
            'credit_limit' => 'límite de crédito',
            'currency_id' => 'moneda',
            'payment_terms' => 'términos de pago',
            'notes' => 'notas',
            'is_active' => 'activo',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es obligatorio.',
            'code.unique' => 'Este código ya está en uso.',
            'email_billing.email' => 'El email de facturación debe ser válido.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'status.in' => 'El estado debe ser: prospecto, activo o inactivo.',
            'credit_limit.numeric' => 'El límite de crédito debe ser numérico.',
            'tax_id.size' => 'El RNC/CEDULA no tiene la longitud correcta.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('tax_id', 'size:9', function ($input) {
            return $input->tax_id_type === 'RNC';
        });

        $validator->sometimes('tax_id', 'size:11', function ($input) {
            return $input->tax_id_type === 'CEDULA';
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && $this->code) {
            $this->merge(['code' => strtoupper($this->code)]);
        }

        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }

        foreach (['credit_limit', 'currency_id'] as $field) {
            if ($this->has($field) && $this->$field === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
