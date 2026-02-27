<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\PickupOrder::class);
    }

    public function rules(): array
    {
        return [
            'shipping_order_id' => 'nullable|exists:shipping_orders,id',
            'customer_id' => 'required|exists:customers,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'reference' => 'nullable|string|max:255',
            'scheduled_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',

            // Stops (optional on create)
            'stops' => 'nullable|array',
            'stops.*.name' => 'required_with:stops|string|max:255',
            'stops.*.address' => 'required_with:stops|string',
            'stops.*.city' => 'nullable|string|max:100',
            'stops.*.country' => 'nullable|string|max:100',
            'stops.*.window_start' => 'nullable|date',
            'stops.*.window_end' => 'nullable|date|after_or_equal:stops.*.window_start',
            'stops.*.contact_name' => 'nullable|string|max:255',
            'stops.*.contact_phone' => 'nullable|string|max:50',
            'stops.*.notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'shipping_order_id.exists' => 'La orden de envío seleccionada no existe.',
            'driver_id.exists' => 'El conductor seleccionado no existe.',
        ];
    }
}
