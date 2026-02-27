<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:100'],
            'contact_type' => ['nullable', 'string', Rule::in(['general', 'billing', 'operations', 'sales'])],
            'is_primary' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'position' => 'cargo',
            'contact_type' => 'tipo de contacto',
            'is_primary' => 'contacto principal',
            'notes' => 'notas',
            'is_active' => 'activo',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del contacto es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'contact_type.in' => 'El tipo de contacto debe ser: general, facturación, operaciones o ventas.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_primary')) {
            $this->merge(['is_primary' => filter_var($this->is_primary, FILTER_VALIDATE_BOOLEAN)]);
        }

        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}
