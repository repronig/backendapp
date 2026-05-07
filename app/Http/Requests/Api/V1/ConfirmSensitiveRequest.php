<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmSensitiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'admin_pin' => ['required', 'digits:6'],
            'challenge_id' => ['nullable', 'integer'],
            'code' => ['nullable', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_pin.required' => 'Enter your 6 digit admin PIN to continue.',
            'admin_pin.digits' => 'The admin PIN must be exactly 6 digits.',
        ];
    }
}
