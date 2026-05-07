<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSuperUserRequest extends FormRequest
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
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($user?->id),
            ],
            'account_type' => ['sometimes', 'string', 'in:member,association_officer,institution_user,admin,super_admin'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
            'password' => ['sometimes', 'string', 'min:8'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'association_ids' => ['sometimes', 'array'],
            'association_ids.*' => ['integer', 'exists:associations,id'],
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
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
