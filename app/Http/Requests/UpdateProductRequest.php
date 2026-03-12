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
        $productId = $this->route('product')?->id;

        return [

            'sku' => [
                'sometimes',
                'nullable',
                'string',
                Rule::unique('products', 'sku')->ignore($productId)
            ],

            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                Rule::unique('products', 'barcode')->ignore($productId)
            ],

            'name' => ['sometimes', 'required', 'string'],

            'description' => ['sometimes', 'nullable', 'string'],

            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],

            'supplier_id' => ['sometimes', 'nullable', 'exists:suppliers,id'],

            'stock' => ['sometimes', 'integer', 'min:0'],

            'reorder_level' => ['sometimes', 'integer', 'min:0'],

            'units_id' => ['sometimes', 'required', 'exists:units,id'],

            'cost_price' => ['sometimes', 'nullable', 'numeric'],

            'default_tax_type' => [
                'sometimes',
                Rule::in(Product::TAX_TYPES)
            ],

            'default_tax_rate' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100'
            ],

            'status' => [
                'sometimes',
                Rule::in(Product::STATUSES)
            ],

            'image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096'
            ],

            'prices' => ['sometimes', 'array'],

            'prices.*.currency_id' => [
                'required_with:prices',
                'exists:currencies,id'
            ],

            'prices.*.price' => [
                'required_with:prices',
                'numeric',
                'min:0.01'
            ],
        ];
    }
}