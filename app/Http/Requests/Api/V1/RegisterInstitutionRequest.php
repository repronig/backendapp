<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\RecaptchaToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'registration_number',
            'contact_person_title',
            'address_line_2',
            'postal_code',
            'academic_staff_count',
            'administrative_staff_count',
            'campuses_count',
            'branches_count',
            'member_count',
            'licensing_year',
            'declared_students_count',
            'declared_members_count',
            'declared_branches_count',
        ];

        $data = $this->all();

        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        foreach (['phone', 'registration_number'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        $rules = [
            'organisation_name' => ['required', 'string', 'max:255'],
            'institution_type' => [
                'required',
                Rule::in([
                    'university',
                    'polytechnic',
                    'college_of_education',
                    'professional_body',
                    'religious_organization',
                    'corporate_organization',
                    'government_agency',
                    'ngo',
                    'research_institute',
                    'library',
                    'other',
                ]),
            ],
            'registration_number' => ['nullable', 'string', 'max:120', Rule::unique('institutions', 'registration_number')],
            'contact_person_name' => ['required', 'string', 'max:255'],
            'contact_person_title' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('institutions', 'email'), Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'year_established' => ['required', 'integer', 'digits:4', 'min:1800', 'max:'.now()->year],
            'academic_staff_count' => ['nullable', 'integer', 'min:0'],
            'administrative_staff_count' => ['nullable', 'integer', 'min:0'],
            'campuses_count' => ['nullable', 'integer', 'min:0'],
            'branches_count' => ['nullable', 'integer', 'min:0'],
            'member_count' => ['nullable', 'integer', 'min:0'],
            'licensing_year' => ['nullable', 'integer', 'digits:4', 'min:2000'],
            'declared_students_count' => ['nullable', 'integer', 'min:0'],
            'declared_members_count' => ['nullable', 'integer', 'min:0'],
            'declared_branches_count' => ['nullable', 'integer', 'min:0'],
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
            'institution_type.required' => 'Select institution type.',
            'institution_type.in' => 'Select institution type.',
            'registration_number.unique' => 'An institution with this CAC registration number already exists.',
            'email.unique' => 'This email address is already in use. Please log in or use another email address.',
            'phone.unique' => 'This phone number is already in use. Please use another phone number or log in to your existing account.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = (string) $this->input('institution_type');
            if ($type === 'professional_body' && ! $this->filled('declared_members_count')) {
                $validator->errors()->add('declared_members_count', 'Declared member count is required for professional bodies.');
            }

            if ($type === 'religious_organization' && ! $this->filled('declared_branches_count')) {
                $validator->errors()->add('declared_branches_count', 'Declared branches count is required for religious organizations.');
            }
        });
    }
}
