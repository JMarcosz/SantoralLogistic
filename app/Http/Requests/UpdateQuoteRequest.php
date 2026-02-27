<?php

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $quote = $this->route('quote');

        return $this->user()->can('update', $quote);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Header fields
            'customer_id' => ['required', 'exists:customers,id'],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'issuing_company_id' => ['nullable', 'exists:company_settings,id'],
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'shipper_id' => ['nullable', 'exists:customers,id'],
            'consignee_id' => ['nullable', 'exists:customers,id'],
            'transit_days' => ['nullable', 'integer', 'min:0'],
            'incoterms' => ['nullable', 'string', 'max:20'],
            'origin_port_id' => ['required', 'exists:ports,id'],
            'destination_port_id' => ['required', 'exists:ports,id', 'different:origin_port_id'],
            'transport_mode_id' => ['required', 'exists:transport_modes,id'],
            'service_type_id' => ['required', 'exists:service_types,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:10000'],
            'payment_terms_id' => ['nullable', 'integer', 'exists:terms,id'],
            'footer_terms_id' => ['nullable', 'integer', 'exists:terms,id'],

            // Cargo details
            'total_pieces' => ['nullable', 'integer', 'min:0'],
            'total_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'total_volume_cbm' => ['nullable', 'numeric', 'min:0'],

            // Lines - at least one required
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'exists:quote_lines,id'],
            'lines.*.product_service_id' => ['required', 'exists:products_services,id'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'lines.*.unit_cost' => $this->user()->hasRole(['admin', 'super-admin', 'owner'])
                ? ['nullable', 'numeric', 'min:0']
                : ['prohibited'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'between:0,100'],

            // Commodities (Items)
            'items' => ['nullable', 'array'],
            'items.*.type' => ['required', 'string', 'in:container,vehicle,loose_cargo'],
            'items.*.identifier' => ['nullable', 'string', 'max:255'],
            'items.*.seal_number' => ['nullable', 'string', 'max:255'],
            'items.*.properties' => ['nullable', 'array'],
            'items.*.lines' => ['required', 'array', 'min:1'],
            'items.*.lines.*.pieces' => ['required', 'integer', 'min:1'],
            'items.*.lines.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.lines.*.weight_kg' => ['required', 'numeric', 'min:0'],
            'items.*.lines.*.volume_cbm' => ['required', 'numeric', 'min:0'],
            'items.*.lines.*.marks_numbers' => ['nullable', 'string', 'max:255'],
            'items.*.lines.*.hs_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es requerido.',
            'origin_port_id.required' => 'El puerto de origen es requerido.',
            'destination_port_id.required' => 'El puerto de destino es requerido.',
            'destination_port_id.different' => 'El puerto de destino debe ser diferente al de origen.',
            'transport_mode_id.required' => 'El modo de transporte es requerido.',
            'service_type_id.required' => 'El tipo de servicio es requerido.',
            'currency_id.required' => 'La moneda es requerida.',
            'lines.required' => 'Debe agregar al menos una línea.',
            'lines.min' => 'Debe agregar al menos una línea.',
            'lines.*.quantity.gt' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
