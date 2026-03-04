<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuppliersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('suppliers', 'email')],
            'city'         => ['required', 'string', 'max:255'],
            'total_price'  => ['required', 'numeric', 'min:0'],
            'total_orders' => ['required', 'integer', 'min:0'],
            'phone'        => ['nullable', 'string', 'max:20'],
            'status'       => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
