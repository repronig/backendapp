<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['phone']) && is_string($data['phone'])) {
            $data['phone'] = trim($data['phone']);
            if ($data['phone'] === '') {
                $data['phone'] = null;
            }
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already in use. Please use another phone number or contact support.',
        ];
    }
}
