<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100' ,Rule::unique('units', 'name')],
            'code' => ['required', 'string', 'max:50', Rule::unique('units', 'code')],
            'description' => ['nullable', 'string'],
        ];
    }
}
