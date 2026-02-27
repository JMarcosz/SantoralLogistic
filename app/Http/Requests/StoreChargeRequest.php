<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChargeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageCharges', $this->route('shipping_order'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'charge_type' => ['required', 'string', Rule::in(['freight', 'surcharge', 'tax', 'other'])],
            'basis' => ['required', 'string', Rule::in(['flat', 'per_kg', 'per_cbm', 'per_shipment'])],
            'currency_code' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'is_tax_included' => ['boolean'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código del cargo es obligatorio.',
            'description.required' => 'La descripción del cargo es obligatoria.',
            'charge_type.in' => 'El tipo de cargo debe ser: flete, recargo, impuesto u otro.',
            'basis.in' => 'La base debe ser: fijo, por kg, por cbm o por envío.',
            'currency_code.exists' => 'La moneda seleccionada no es válida.',
            'unit_price.required' => 'El precio unitario es obligatorio.',
            'unit_price.min' => 'El precio unitario no puede ser negativo.',
            'qty.required' => 'La cantidad es obligatoria.',
            'qty.min' => 'La cantidad debe ser mayor a cero.',
        ];
    }
}
