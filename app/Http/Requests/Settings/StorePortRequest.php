<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('ports.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:ports,code'],
            'name' => ['required', 'string', 'max:150'],
            'country' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'unlocode' => ['nullable', 'string', 'max:10'],
            'iata_code' => ['nullable', 'string', 'max:5'],
            'type' => ['required', Rule::in(['air', 'ocean', 'ground'])],
            'timezone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'country' => 'país',
            'city' => 'ciudad',
            'unlocode' => 'UN/LOCODE',
            'iata_code' => 'código IATA',
            'type' => 'tipo',
            'timezone' => 'zona horaria',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código del puerto es obligatorio.',
            'code.max' => 'El código no puede exceder los 20 caracteres.',
            'code.unique' => 'Este código de puerto ya existe.',
            'name.required' => 'El nombre del puerto es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 150 caracteres.',
            'country.required' => 'El país es obligatorio.',
            'country.max' => 'El país no puede exceder los 100 caracteres.',
            'type.required' => 'El tipo de puerto es obligatorio.',
            'type.in' => 'El tipo debe ser: air, ocean o ground.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert code to uppercase
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }

        // Convert is_active to boolean if present
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
