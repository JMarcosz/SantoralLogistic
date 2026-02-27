<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('rates.update');
    }

    public function rules(): array
    {
        return [
            'origin_port_id' => ['required', 'exists:ports,id'],
            'destination_port_id' => ['required', 'exists:ports,id', 'different:origin_port_id'],
            'transport_mode_id' => ['required', 'exists:transport_modes,id'],
            'service_type_id' => ['required', 'exists:service_types,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'charge_basis' => ['required', 'string', Rule::in(['per_shipment', 'per_kg', 'per_cbm', 'per_container'])],
            'base_amount' => ['required', 'numeric', 'min:0.0001', 'max:99999999999.9999'],
            'min_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999999.9999'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'origin_port_id' => 'puerto origen',
            'destination_port_id' => 'puerto destino',
            'transport_mode_id' => 'modo de transporte',
            'service_type_id' => 'tipo de servicio',
            'currency_id' => 'moneda',
            'charge_basis' => 'base de cargo',
            'base_amount' => 'monto base',
            'min_amount' => 'monto mínimo',
            'valid_from' => 'válido desde',
            'valid_to' => 'válido hasta',
            'is_active' => 'estado activo',
        ];
    }

    public function messages(): array
    {
        return [
            'origin_port_id.required' => 'El puerto de origen es obligatorio.',
            'destination_port_id.required' => 'El puerto de destino es obligatorio.',
            'destination_port_id.different' => 'El puerto de destino debe ser diferente al de origen.',
            'transport_mode_id.required' => 'El modo de transporte es obligatorio.',
            'service_type_id.required' => 'El tipo de servicio es obligatorio.',
            'currency_id.required' => 'La moneda es obligatoria.',
            'base_amount.required' => 'El monto base es obligatorio.',
            'base_amount.min' => 'El monto base debe ser mayor a 0.',
            'valid_from.required' => 'La fecha de inicio es obligatoria.',
            'valid_to.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }

        if ($this->has('min_amount') && $this->min_amount === '') {
            $this->merge(['min_amount' => null]);
        }

        if ($this->has('valid_to') && $this->valid_to === '') {
            $this->merge(['valid_to' => null]);
        }
    }
}
