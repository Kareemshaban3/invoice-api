<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!is_array($this->input('prices'))) return;

        $prices = collect($this->input('prices'))
            ->filter(fn($row) => is_array($row))
            ->map(function ($row) {
                if (isset($row['currency']) && is_string($row['currency'])) {
                    $row['currency'] = strtoupper(trim($row['currency']));
                }
                return $row;
            })
            ->values()
            ->all();

        $this->merge(['prices' => $prices]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],

            'stock' => ['sometimes', 'required', 'integer', 'min:0'],

            'prices' => ['sometimes', 'array', 'min:1'],
            'prices.*.currency' => ['required_with:prices', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', 'distinct'],
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0'],
        ];
    }
}
