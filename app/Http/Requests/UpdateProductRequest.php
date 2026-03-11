<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                // نتأكد إن currency_id رقم صحيح
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
        $productId = $this->route('product')?->id ?? null;

        return [
            'sku' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($productId)],

            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],

            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'supplier_id' => ['sometimes', 'nullable', 'integer', 'exists:suppliers,id'],

            'stock' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'reorder_level' => ['sometimes', 'nullable', 'integer', 'min:0'],

            'units_id' => ['sometimes', 'required', 'integer', 'exists:units,id'],

            'cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0.01'],

            'default_tax_type' => ['sometimes', 'required', Rule::in(Product::TAX_TYPES)],
            'default_tax_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],

            'status' => ['sometimes', 'required', Rule::in(Product::STATUSES)],

            'image' => ['sometimes', 'nullable', 'image', 'max:4096'],

            'prices' => ['sometimes', 'nullable', 'array'],
            'prices.*.currency_id' => ['required_with:prices', 'integer', 'exists:currencies,id'], // تعديل هنا
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0.01'],
        ];
    }
}