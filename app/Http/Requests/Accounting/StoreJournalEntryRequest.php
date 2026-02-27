<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.currency_code' => ['nullable', 'string', 'size:3'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es requerida.',
            'description.required' => 'La descripción es requerida.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
            'lines.required' => 'El asiento debe tener líneas.',
            'lines.min' => 'El asiento debe tener al menos 2 líneas.',
            'lines.*.account_id.required' => 'Cada línea debe tener una cuenta.',
            'lines.*.account_id.exists' => 'La cuenta seleccionada no existe.',
            'lines.*.debit.required' => 'El débito es requerido.',
            'lines.*.debit.min' => 'El débito no puede ser negativo.',
            'lines.*.credit.required' => 'El crédito es requerido.',
            'lines.*.credit.min' => 'El crédito no puede ser negativo.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = $this->input('lines', []);

            // Validate at least one debit and one credit
            $hasDebit = false;
            $hasCre = false;

            foreach ($lines as $line) {
                if (($line['debit'] ?? 0) > 0) {
                    $hasDebit = true;
                }
                if (($line['credit'] ?? 0) > 0) {
                    $hasCre = true;
                }
            }

            if (!$hasDebit) {
                $validator->errors()->add('lines', 'El asiento debe tener al menos una línea de débito.');
            }

            if (!$hasCre) {
                $validator->errors()->add('lines', 'El asiento debe tener al menos una línea de crédito.');
            }

            // Validate each line has either debit or credit (not both, not neither)
            foreach ($lines as $index => $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add(
                        "lines.{$index}",
                        'Una línea no puede tener débito y crédito al mismo tiempo.'
                    );
                }

                if ($debit === 0.0 && $credit === 0.0) {
                    $validator->errors()->add(
                        "lines.{$index}",
                        'Cada línea debe tener un monto de débito o crédito.'
                    );
                }
            }
        });
    }
}
