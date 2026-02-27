<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePutawayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('putaway', $this->route('item'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $item = $this->route('item');

        return [
            'location_id' => [
                'required',
                'integer',
                'exists:locations,id',
                // Ensure location belongs to the same warehouse as the item
                function ($attribute, $value, $fail) use ($item) {
                    $location = \App\Models\Location::find($value);
                    if ($location && $item && $location->warehouse_id !== $item->warehouse_id) {
                        $fail('La ubicación debe pertenecer al mismo almacén que el ítem.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'location_id.required' => 'La ubicación es requerida.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'location_id' => 'ubicación',
        ];
    }
}
