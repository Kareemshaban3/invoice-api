<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepresentativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['individual', 'company'])],

            'phone' => ['nullable', 'string', 'max:50', Rule::unique('representatives', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('representatives', 'email')],

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
            'country_code' => ['nullable', 'string', 'max:5'],
            'address' => ['nullable', 'string'],

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
                    $validator->errors()->add('tax_number', 'Either tax_number or commercial_register is required for companies.');
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
