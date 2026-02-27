<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransportModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transport_modes.update');
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('transport_modes', 'code')->ignore($this->route('transport_mode')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'supports_awb' => ['boolean'],
            'supports_bl' => ['boolean'],
            'supports_pod' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'supports_awb' => 'soporta AWB',
            'supports_bl' => 'soporta B/L',
            'supports_pod' => 'soporta POD',
            'is_active' => 'estado activo',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.unique' => 'Este código ya existe.',
            'name.required' => 'El nombre es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper($this->code)]);
        }

        foreach (['supports_awb', 'supports_bl', 'supports_pod', 'is_active'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
