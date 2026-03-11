<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuppliersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('suppliers', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers', 'email')],

            'country' => ['nullable', Rule::in([
                'Egypt','Saudi Arabia','Oman','Jordan','Bahrain','Algeria','Sudan','Syria','Palestine','Iraq',
                'Qatar','Kuwait','Lebanon','Libya','Morocco','Yemen','Tunisia','Somalia',
            ])],

            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],

            'tax_number' => ['nullable', 'string', 'max:100'],

            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],

            'default_payment_method' => ['nullable', Rule::in(['cash', 'transfer', 'card', 'credit'])],

            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:64'],

            'category_id' => ['nullable', 'integer', 'exists:categories,id'],

            'status' => ['required', Rule::in(['active', 'suspended', 'archived'])],

            'notes' => ['nullable', 'string'],

            'total_price' => ['nullable', 'numeric', 'min:0'],
            'total_orders' => ['nullable', 'integer', 'min:0'],
        ];
    }
}