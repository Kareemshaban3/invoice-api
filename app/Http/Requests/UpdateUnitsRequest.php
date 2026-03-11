<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')?->id ?? null;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('units', 'name')->ignore($unitId),

            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'code')->ignore($unitId),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
