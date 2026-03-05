<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes', 'required', 'integer', 'exists:clients,id'],
            'date' => ['sometimes', 'required', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],

            'currency' => ['sometimes', 'required', 'string', 'size:3'],

            'payment_method' => ['sometimes', 'nullable', Rule::in(['cash', 'transfer', 'card', 'credit'])],
            'payment_status' => ['sometimes', 'nullable', Rule::in(['draft', 'unpaid', 'partial', 'paid', 'cancelled'])],

            'discount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'paid' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'notes' => ['sometimes', 'nullable', 'string'],

            'items' => ['sometimes', 'required', 'array', 'min:1'],

            'items.*.item_type' => ['nullable', Rule::in(['product', 'service'])],

            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],

            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],

            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.000001'],

            'items.*.discount_type' => ['nullable', Rule::in(['none', 'amount', 'percent'])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],

            'items.*.tax_type' => ['nullable', Rule::in(['no_tax', 'exclusive', 'inclusive'])],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->has('items')) return;

            $items = $this->input('items', []);
            foreach ($items as $i => $row) {
                $type = $row['item_type'] ?? 'product';

                if ($type === 'product') {
                    if (empty($row['product_id'])) {
                        $validator->errors()->add("items.$i.product_id", 'product_id is required for product items');
                    }
                } else {
                    if (empty($row['name'])) {
                        $validator->errors()->add("items.$i.name", 'name is required for service items');
                    }
                    if (!array_key_exists('unit_price', $row)) {
                        $validator->errors()->add("items.$i.unit_price", 'unit_price is required for service items');
                    }
                }

                $discountType = $row['discount_type'] ?? 'none';
                $discountValue = (float) ($row['discount_value'] ?? 0);

                if ($discountType === 'percent' && ($discountValue < 0 || $discountValue > 100)) {
                    $validator->errors()->add("items.$i.discount_value", 'discount_value must be between 0 and 100 for percent discount');
                }

                $taxType = $row['tax_type'] ?? 'no_tax';
                $taxRate = (float) ($row['tax_rate'] ?? 0);

                if ($taxType !== 'no_tax' && ($taxRate <= 0)) {
                    $validator->errors()->add("items.$i.tax_rate", 'tax_rate must be > 0 when tax_type is exclusive/inclusive');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge(['currency' => strtoupper((string) $this->input('currency'))]);
        }
    }
}
