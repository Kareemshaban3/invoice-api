<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id', 'required_without:representatives_id'],
            'representatives_id' => ['nullable', 'integer', 'exists:representatives,id', 'required_without:client_id'],
            'branches_id' => ['nullable', 'integer', 'exists:branches,id'],

            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],

            'payment_method' => ['nullable', Rule::in(['cash', 'transfer', 'card', 'credit'])],
            'payment_status' => ['nullable', Rule::in(['draft', 'unpaid', 'partial', 'paid', 'cancelled'])],

            'discount' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', Rule::in(['product', 'service'])],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'items.*.discount_type' => ['nullable', Rule::in(['none', 'amount', 'percent'])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_type' => ['nullable', Rule::in(['no_tax', 'exclusive', 'inclusive'])],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            foreach ($items as $i => $row) {
                $type = $row['item_type'] ?? null;

                if ($type === 'product') {
                    if (empty($row['product_id'])) {
                        $validator->errors()->add("items.$i.product_id", 'Product ID is required for product items.');
                    }
                }

                if ($type === 'service') {
                    if (empty($row['name'])) {
                        $validator->errors()->add("items.$i.name", 'Name is required for service items.');
                    }

                    if (!isset($row['unit_price']) || $row['unit_price'] === '') {
                        $validator->errors()->add("items.$i.unit_price", 'Unit price is required for service items.');
                    }
                }

                $discountType = $row['discount_type'] ?? 'none';
                $discountValue = (float) ($row['discount_value'] ?? 0);

                if ($discountType === 'percent' && ($discountValue < 0 || $discountValue > 100)) {
                    $validator->errors()->add("items.$i.discount_value", 'Discount value must be between 0 and 100 for percent discount.');
                }

                $taxType = $row['tax_type'] ?? 'no_tax';
                $taxRate = (float) ($row['tax_rate'] ?? 0);

                if (in_array($taxType, ['exclusive', 'inclusive'], true) && $taxRate <= 0) {
                    $validator->errors()->add("items.$i.tax_rate", 'Tax rate must be greater than 0 when tax_type is exclusive/inclusive.');
                }
            }

            if ($this->filled('client_id') && $this->filled('representatives_id')) {
                $validator->errors()->add('client_id', 'لا يمكن اختيار العميل والمندوب معًا.');
            }

            if (!$this->filled('client_id') && !$this->filled('representatives_id')) {
                $validator->errors()->add('client_id', 'يجب اختيار العميل أو المندوب.');
            }
        });
    }
}