<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAdminAssociationOfficerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('email') && ! $this->filled('password')) {
                $validator->errors()->add('email', 'Provide an email address or a new password to update.');
            }
        });
    }
}
