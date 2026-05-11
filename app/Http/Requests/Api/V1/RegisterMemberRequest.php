<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\RecaptchaToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        if (isset($data['phone']) && is_string($data['phone'])) {
            $data['phone'] = trim($data['phone']);
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')],
            'nationality' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'applicant_type' => ['required', 'in:author,publisher,corporate_publisher'],
            'association_id' => ['required', 'integer', 'exists:associations,id'],
            'accepted_terms' => ['accepted'],
        ];

        if (RecaptchaToken::enabled()) {
            $rules['recaptcha_token'] = ['required', 'string', new RecaptchaToken];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already in use. Please log in or use another email address.',
            'phone.unique' => 'This phone number is already in use. Please use another phone number or log in to your existing account.',
        ];
    }
}
