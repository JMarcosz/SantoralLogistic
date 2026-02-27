<?php

namespace App\Http\Requests\Accounting;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
        $accountId = $this->route('account')->id;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('accounts', 'code')->ignore($accountId),
                'regex:/^[A-Z0-9\-]+$/',
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
            'parent_id' => [
                'nullable',
                'exists:accounts,id',
                Rule::notIn([$accountId]), // Cannot be its own parent
            ],
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
            $account = $this->route('account');

            // CRITICAL: If parent exists, parent must NOT be postable
            if ($this->filled('parent_id')) {
                $parent = Account::find($this->parent_id);

                if ($parent && $parent->is_postable) {
                    $validator->errors()->add(
                        'parent_id',
                        'La cuenta padre debe ser una cuenta de agrupación (no posteable).'
                    );
                }

                // Prevent circular reference
                if ($this->wouldCreateCircularReference($this->parent_id, $account->id)) {
                    $validator->errors()->add(
                        'parent_id',
                        'No se puede crear una referencia circular en la jerarquía de cuentas.'
                    );
                }
            }

            // CRITICAL: Cannot change is_postable to false if has children
            if ($this->has('is_postable') && $this->is_postable === false) {
                if ($account->hasChildren()) {
                    $validator->errors()->add(
                        'is_postable',
                        'No se puede cambiar a cuenta de agrupación porque tiene cuentas hijas.'
                    );
                }

                // TODO: When JournalEntryLine exists, validate
                // if (!$account->canChangeToNonPostable()) {
                //     $validator->errors()->add(
                //         'is_postable',
                //         'No se puede cambiar a cuenta de agrupación porque tiene asientos contables.'
                //     );
                // }
            }

            // Validate normal_balance matches type
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
     * Check if parent assignment would create circular reference.
     */
    private function wouldCreateCircularReference($parentId, $accountId): bool
    {
        $current = Account::find($parentId);

        while ($current) {
            if ($current->id === $accountId) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
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
            'type.in' => 'El tipo de cuenta debe ser: asset, liability, equity, revenue o expense.',
            'parent_id.not_in' => 'Una cuenta no puede ser su propia cuenta padre.',
        ];
    }
}
