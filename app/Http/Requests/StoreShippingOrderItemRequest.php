<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:container,vehicle,loose_cargo'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'seal_number' => ['nullable', 'string', 'max:255'],
            'properties' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.pieces' => ['required', 'integer', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:65535'],
            'lines.*.weight_kg' => ['required', 'numeric', 'min:0'],
            'lines.*.volume_cbm' => ['required', 'numeric', 'min:0'],
            'lines.*.marks_numbers' => ['nullable', 'string', 'max:255'],
            'lines.*.hs_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
