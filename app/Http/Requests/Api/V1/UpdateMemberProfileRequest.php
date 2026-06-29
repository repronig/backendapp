<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('member') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['phone']) && is_string($data['phone'])) {
            $data['phone'] = trim($data['phone']);
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'min:1', 'max:100'],
            'last_name' => ['sometimes', 'string', 'min:1', 'max:100'],
            'phone' => [
                'required',
                'string',
                'min:1',
                'max:30',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
            'date_of_birth' => ['nullable', 'date'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'residential_address_line_1' => ['nullable', 'string', 'max:255'],
            'residential_address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'publisher_name' => ['nullable', 'string', 'max:255'],
            'corporate_name' => ['nullable', 'string', 'max:255'],
            'member_provided_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already in use. Please use another phone number or contact support.',
        ];
    }
}
