<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currencyId = $this->route('currency')?->id ?? null;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('currencies', 'name')->ignore($currencyId),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'size:3',
                Rule::unique('currencies', 'code')->ignore($currencyId),
            ],
            'conversion_rate' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.000001',
            ],
            'status' => [
                'sometimes',
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
    }
}
