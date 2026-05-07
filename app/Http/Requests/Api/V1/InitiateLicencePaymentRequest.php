<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class InitiateLicencePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway_name' => ['required', 'in:paystack,flutterwave'],
            'callback_url' => ['nullable', 'url'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'licensing_year' => ['nullable', 'integer', 'digits:4'],
            'licence_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
