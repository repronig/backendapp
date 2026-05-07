<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuperUserRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8'],
            'account_type' => ['required', 'string', 'in:member,association_officer,institution_user,admin,super_admin'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'association_ids' => ['sometimes', 'array'],
            'association_ids.*' => ['integer', 'exists:associations,id'],
            'institution_id' => ['sometimes', 'integer', 'exists:institutions,id'],
            'institution_role_label' => ['sometimes', 'string', 'max:100'],
            'institution_is_primary' => ['sometimes', 'boolean'],
            'institution_is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already assigned to another user account.',
        ];
    }
}
