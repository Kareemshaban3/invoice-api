<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSuppliersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('suppliers', 'phone')->ignore($supplierId),
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($supplierId),
            ],

            'country' => ['sometimes', 'nullable', Rule::in([
                'Egypt','Saudi Arabia','Oman','Jordan','Bahrain','Algeria','Sudan','Syria','Palestine','Iraq',
                'Qatar','Kuwait','Lebanon','Libya','Morocco','Yemen','Tunisia','Somalia',
            ])],

            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],

            'tax_number' => ['sometimes', 'nullable', 'string', 'max:100'],

            'payment_terms_days' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:3650'],
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'opening_balance' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'default_payment_method' => ['sometimes', 'nullable', Rule::in(['cash', 'transfer', 'card', 'credit'])],

            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bank_account_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'iban' => ['sometimes', 'nullable', 'string', 'max:64'],

            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],

            'status' => ['sometimes', 'required', Rule::in(['active', 'suspended', 'archived'])],

            'notes' => ['sometimes', 'nullable', 'string'],

            'total_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_orders' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}