<?php

namespace App\Http\Requests\Accounting;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('accounting.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:accounts,code',
                'regex:/^[A-Z0-9\-]+$/', // Only uppercase letters, numbers, and hyphens
            ],
            'name' => 'required|string|max:255',
            'type' => [
                'required',
                Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense']),
            ],
            'normal_balance' => [
                'required',
                Rule::in(['debit', 'credit']),
            ],
            'parent_id' => 'nullable|exists:accounts,id',
            'is_postable' => 'boolean',
            'requires_subsidiary' => 'boolean',
            'is_active' => 'boolean',
            'currency_code' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // CRITICAL: If parent exists, parent must NOT be postable (must be header)
            if ($this->filled('parent_id')) {
                $parent = Account::find($this->parent_id);

                if ($parent && $parent->is_postable) {
                    $validator->errors()->add(
                        'parent_id',
                        'La cuenta padre debe ser una cuenta de agrupación (no posteable).'
                    );
                }
            }

            // Business rule: Validate normal_balance matches account type
            if ($this->filled('type') && $this->filled('normal_balance')) {
                $expectedBalance = $this->getExpectedBalance($this->type);

                if ($this->normal_balance !== $expectedBalance) {
                    $validator->errors()->add(
                        'normal_balance',
                        "Las cuentas de tipo '{$this->type}' normalmente tienen balance '{$expectedBalance}'."
                    );
                }
            }
        });
    }

    /**
     * Get expected normal balance for account type.
     */
    private function getExpectedBalance(string $type): string
    {
        return match ($type) {
            'asset', 'expense' => 'debit',
            'liability', 'equity', 'revenue' => 'credit',
        };
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código de cuenta es obligatorio.',
            'code.unique' => 'Este código de cuenta ya existe.',
            'code.regex' => 'El código debe contener solo letras mayúsculas, números y guiones.',
            'name.required' => 'El nombre de la cuenta es obligatorio.',
            'type.required' => 'El tipo de cuenta es obligatorio.',
            'type.in' => 'El tipo de cuenta debe ser: asset, liability, equity, revenue o expense.',
            'normal_balance.in' => 'El balance normal debe ser debit o credit.',
            'parent_id.exists' => 'La cuenta padre seleccionada no existe.',
        ];
    }
}
