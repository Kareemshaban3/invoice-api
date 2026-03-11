<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRepresentativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('representative')?->id; // جلب الـ id من الـ route model binding

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::in(['individual', 'company'])],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('representatives', 'phone')->ignore($id),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('representatives', 'email')->ignore($id),
            ],

            'country' => ['sometimes', 'nullable', Rule::in([
                'Egypt','Saudi Arabia','Oman','Jordan','Bahrain','Algeria','Sudan','Syria','Palestine','Iraq',
                'Qatar','Kuwait','Lebanon','Libya','Morocco','Yemen','Tunisia','Somalia',
            ])],

            'address' => ['sometimes', 'nullable', 'string'],

            'tax_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'commercial_register' => ['sometimes', 'nullable', 'string', 'max:100'],

            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'opening_balance' => ['sometimes', 'nullable', 'numeric'],

            'default_payment_method' => ['sometimes', 'nullable', Rule::in(['cash', 'transfer', 'card', 'credit'])],

            'sales_rep_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],

            'internal_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->has('type')) return;

            $type = $this->input('type');
            if ($type === 'company') {
                if (!$this->filled('tax_number') && !$this->filled('commercial_register')) {
                    $validator->errors()->add('tax_number', 'Either tax_number or commercial_register is required for companies.');
                }
            }
        });
    }
}