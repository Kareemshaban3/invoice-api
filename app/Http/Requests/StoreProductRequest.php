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
                if (isset($row['currency_id'])) {
                    $row['currency_id'] = (int) $row['currency_id'];
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

            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],

            'stock' => ['required', 'integer', 'min:1'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],

            'units_id' => ['required', 'integer', 'exists:units,id'],

            'cost_price' => ['required', 'numeric', 'min:0.01'],

            'default_tax_type' => ['required', Rule::in(Product::TAX_TYPES)],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],

            'status' => ['required', Rule::in(Product::STATUSES)],

            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],

            'prices' => ['nullable', 'array'],
            'prices.*.currency_id' => ['required_with:prices', 'integer', 'exists:currencies,id'], // تعديل هنا
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0.01'],
        ];
    }
}
