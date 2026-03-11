<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('currencies', 'name'),
            ],
            'code' => [
                'required',
                'string',
                'size:3',
                Rule::unique('currencies', 'code'),
            ],
            'conversion_rate' => [
                'required',
                'numeric',
                'min:0.000001',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }
}
