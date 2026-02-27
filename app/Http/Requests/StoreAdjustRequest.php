<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdjustRequest extends FormRequest
{
    /**
     * Valid adjustment reasons.
     */
    public const VALID_REASONS = [
        'count_adjustment',
        'damage',
        'expiration',
        'return',
        'correction',
        'other',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('adjust', $this->route('item'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'new_qty' => [
                'required',
                'numeric',
                'min:0',
            ],
            'reason' => [
                'required',
                'string',
                'max:100',
                Rule::in(self::VALID_REASONS),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'new_qty.required' => 'La nueva cantidad es requerida.',
            'new_qty.min' => 'La cantidad no puede ser negativa.',
            'reason.required' => 'El motivo de ajuste es requerido.',
            'reason.in' => 'El motivo de ajuste seleccionado no es válido.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'new_qty' => 'nueva cantidad',
            'reason' => 'motivo',
            'notes' => 'notas',
        ];
    }
}
