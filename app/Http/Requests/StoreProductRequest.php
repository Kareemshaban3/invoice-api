<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],

            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],

            'stock' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],

            'unit' => ['required', Rule::in(Product::UNITS)],

            'cost_price' => ['nullable', 'numeric', 'min:0'],

            'default_tax_type' => ['required', Rule::in(Product::TAX_TYPES)],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],

            'status' => ['required', Rule::in(Product::STATUSES)],

            'image' => ['nullable', 'image', 'max:4096'],

            'prices' => ['nullable', 'array'],
            'prices.*.currency' => ['required_with:prices', 'string', 'size:3'],
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0'],
        ];
    }
}
