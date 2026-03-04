<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendInvoiceEmailRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'to' => ['nullable', 'email', 'max:255'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email'],
        ];
    }
}
