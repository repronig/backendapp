<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'admin_pin' => ['required', 'digits:6', 'confirmed'],
            'admin_pin_confirmation' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_pin.required' => 'Enter a 6 digit admin PIN.',
            'admin_pin.digits' => 'The admin PIN must be exactly 6 digits.',
            'admin_pin.confirmed' => 'The admin PIN confirmation does not match.',
            'admin_pin_confirmation.required' => 'Confirm the 6 digit admin PIN.',
            'admin_pin_confirmation.digits' => 'The admin PIN confirmation must be exactly 6 digits.',
        ];
    }
}
