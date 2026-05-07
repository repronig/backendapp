<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInstitutionProfileRequest extends FormRequest
{
    private const ACADEMIC_INSTITUTION_TYPES = [
        'university',
        'polytechnic',
        'college_of_education',
        'research_institute',
    ];

    public function authorize(): bool
    {
        return $this->user()?->hasRole('institution_user') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'contact_person_title',
            'address_line_2',
            'postal_code',
            'faculties_count',
            'member_count',
            'branches_count',
            'academic_staff_count',
            'administrative_staff_count',
            'campuses_count',
        ];

        $data = $this->all();

        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        return [
            'contact_person_name' => ['sometimes', 'string', 'max:255'],
            'contact_person_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'address_line_1' => ['sometimes', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'max:100'],
            'country' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'year_established' => ['sometimes', 'integer', 'digits:4', 'min:1800', 'max:'.now()->year],
            'faculties_count' => ['nullable', 'integer', 'min:0'],
            'member_count' => ['nullable', 'integer', 'min:0'],
            'branches_count' => ['nullable', 'integer', 'min:0'],
            'institution_type' => [
                'sometimes',
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
            'academic_staff_count' => ['nullable', 'integer', 'min:0'],
            'administrative_staff_count' => ['nullable', 'integer', 'min:0'],
            'campuses_count' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = (string) $this->input('institution_type', '');

            if ($type === '') {
                return;
            }

            if (in_array($type, self::ACADEMIC_INSTITUTION_TYPES, true)) {
                foreach (['faculties_count', 'academic_staff_count', 'administrative_staff_count', 'campuses_count'] as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, 'This field is required for universities, polytechnics, colleges of education, and research institutes.');
                    }
                }

                return;
            }

            foreach (['member_count', 'branches_count'] as $field) {
                if (! $this->filled($field)) {
                    $validator->errors()->add($field, 'This field is required for this institution type.');
                }
            }
        });
    }
}
