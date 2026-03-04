<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('currency'))) {
            $this->merge(['currency' => strtoupper($this->input('currency'))]);
        }
    }

    public function rules(): array
    {
        return [
            'client_id' => ['sometimes','integer','exists:clients,id'],
            'currency' => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],

            'date' => ['sometimes','date'],
            'due_date' => ['sometimes','nullable','date'],

            'discount' => ['sometimes','numeric','min:0'],
            'paid' => ['sometimes','numeric','min:0'],
            'notes' => ['sometimes','nullable','string'],

            'items' => ['sometimes','array','min:1'],
            'items.*.product_id' => ['required_with:items','integer','exists:products,id'],
            'items.*.quantity' => ['required_with:items','numeric','min:0.01'],
        ];
    }
}
