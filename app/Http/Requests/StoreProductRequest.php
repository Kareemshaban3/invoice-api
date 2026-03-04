<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')],
            'description' => ['nullable', 'string'],

            'stock' => ['required', 'integer', 'min:0'],

            'prices' => ['required', 'array', 'min:1'],
            'prices.*.currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/', 'distinct'],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
