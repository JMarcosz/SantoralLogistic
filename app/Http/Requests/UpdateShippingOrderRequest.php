<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingOrderRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * Set default values for nullable fields.
     */
    protected function prepareForValidation(): void
    {
        // Don't override existing values with 0, only fill if completely null from frontend
        $data = [];
        if ($this->has('total_amount') && $this->input('total_amount') === null) {
            $data['total_amount'] = 0;
        }
        if ($this->has('total_pieces') && $this->input('total_pieces') === null) {
            $data['total_pieces'] = 0;
        }
        if ($this->has('total_weight_kg') && $this->input('total_weight_kg') === null) {
            $data['total_weight_kg'] = 0;
        }
        if ($this->has('total_volume_cbm') && $this->input('total_volume_cbm') === null) {
            $data['total_volume_cbm'] = 0;
        }
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('shippingOrder'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'shipper_id' => 'nullable|exists:customers,id',
            'consignee_id' => 'nullable|exists:customers,id',
            'origin_port_id' => 'required|exists:ports,id',
            'destination_port_id' => 'required|exists:ports,id|different:origin_port_id',
            // allow nullable values on update as well
            'transport_mode_id' => 'nullable|exists:transport_modes,id',
            'service_type_id' => 'nullable|exists:service_types,id',
            'currency_id' => 'required|exists:currencies,id',
            'total_amount' => 'nullable|numeric|min:0',
            'total_pieces' => 'nullable|integer|min:0',
            'total_weight_kg' => 'nullable|numeric|min:0',
            'total_volume_cbm' => 'nullable|numeric|min:0',
            'planned_departure_at' => 'nullable|date',
            'planned_arrival_at' => 'nullable|date|after_or_equal:planned_departure_at',
            'pickup_date' => 'nullable|date',
            'delivery_date' => 'nullable|date|after_or_equal:pickup_date',
            'notes' => 'nullable|string|max:2000',
            'footer_terms_id' => 'nullable|integer|exists:terms,id',

            // Ocean shipment (nested object)
            'ocean_shipment' => 'nullable|array',
            'ocean_shipment.mbl_number' => 'nullable|string|max:100',
            'ocean_shipment.hbl_number' => 'nullable|string|max:100',
            'ocean_shipment.carrier_name' => 'nullable|string|max:255',
            'ocean_shipment.vessel_name' => 'nullable|string|max:255',
            'ocean_shipment.voyage_number' => 'nullable|string|max:100',
            'ocean_shipment.container_details' => 'nullable|array',
            'ocean_shipment.container_details.*.container_number' => 'nullable|string|max:50',
            'ocean_shipment.container_details.*.container_type' => 'nullable|string|max:20',
            'ocean_shipment.container_details.*.seal_number' => 'nullable|string|max:50',
            'ocean_shipment.container_details.*.weight_kg' => 'nullable|numeric|min:0',

            // Air shipment (nested object)
            'air_shipment' => 'nullable|array',
            'air_shipment.mawb_number' => 'nullable|string|max:100',
            'air_shipment.hawb_number' => 'nullable|string|max:100',
            'air_shipment.airline_name' => 'nullable|string|max:255',
            'air_shipment.flight_number' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'origin_port_id.required' => 'El puerto de origen es obligatorio.',
            'destination_port_id.required' => 'El puerto de destino es obligatorio.',
            'destination_port_id.different' => 'El puerto de destino debe ser diferente al de origen.',
            'transport_mode_id.exists' => 'El modo de transporte seleccionado no es válido.',
            'service_type_id.exists' => 'El tipo de servicio seleccionado no es válido.',
            'currency_id.required' => 'La moneda es obligatoria.',
            'planned_arrival_at.after_or_equal' => 'La fecha de llegada debe ser posterior a la de salida.',
            'delivery_date.after_or_equal' => 'La fecha de entrega debe ser posterior a la de recogida.',
        ];
    }
}
