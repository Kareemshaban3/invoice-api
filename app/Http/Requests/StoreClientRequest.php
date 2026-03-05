<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // معلومات أساسية
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['individual', 'company'])],

            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],

            // الدولة (Dropdown) نخزن كود

            'country' => ['nullable', Rule::in([
                'Egypt',
                'Saudi Arabia',
                'Oman',
                'Jordan',
                'Bahrain',
                'Algeria',
                'Sudan',
                'Syria',
                'Palestine',
                'Iraq',
                'Qatar',
                'Kuwait',
                'Lebanon',
                'Libya',
                'Morocco',
                'Yemen',
                'Tunisia',
                'Somalia',
            ])],
            'address' => ['nullable', 'string'],

            // معلومات مالية
            'tax_number' => ['nullable', 'string', 'max:100'],
            'commercial_register' => ['nullable', 'string', 'max:100'],

            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],

            'default_payment_method' => ['nullable', Rule::in(['cash', 'transfer', 'card', 'credit'])],

            'sales_rep_id' => ['nullable', 'integer', 'exists:users,id'],

            'internal_notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type', 'individual');

            if ($type === 'company') {
                if (!$this->filled('tax_number') && !$this->filled('commercial_register')) {
                    $validator->errors()->add('tax_number', 'tax_number أو commercial_register مطلوب للشركة');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('country_code')) {
            $this->merge(['country_code' => strtoupper((string) $this->input('country_code'))]);
        }
    }
}
