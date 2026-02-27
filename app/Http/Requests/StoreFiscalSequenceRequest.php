<?php

namespace App\Http\Requests;

use App\Models\FiscalSequence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreFiscalSequenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-fiscal-sequences');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ncf_type' => ['required', 'string', 'max:4'],
            'series' => ['nullable', 'string', 'max:10'],
            'ncf_from' => ['required', 'string', 'max:19'],
            'ncf_to' => ['required', 'string', 'max:19'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['required', 'date', 'after_or_equal:valid_from'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure is_active defaults to true if not provided
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate NCF range order (ncf_from <= ncf_to lexicographically)
            if ($this->ncf_from && $this->ncf_to && strcmp($this->ncf_from, $this->ncf_to) > 0) {
                $validator->errors()->add(
                    'ncf_to',
                    'El NCF final debe ser mayor o igual al NCF inicial.'
                );
            }

            // Check for overlapping ranges
            if (!$validator->errors()->has('ncf_from') && !$validator->errors()->has('ncf_to')) {
                $hasOverlap = FiscalSequence::hasOverlap(
                    $this->ncf_type,
                    $this->series,
                    $this->ncf_from,
                    $this->ncf_to,
                    null // No exclusion for new records
                );

                if ($hasOverlap) {
                    $validator->errors()->add(
                        'ncf_from',
                        'El rango NCF se solapa con un rango existente para este tipo y serie.'
                    );
                }
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'ncf_type' => 'tipo de NCF',
            'series' => 'serie',
            'ncf_from' => 'NCF desde',
            'ncf_to' => 'NCF hasta',
            'valid_from' => 'vigencia desde',
            'valid_to' => 'vigencia hasta',
            'is_active' => 'activo',
        ];
    }
}
