<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLicensingFeePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
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
            'basis_type' => ['required', 'in:per_student,per_member,per_branch,flat_rate'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'flat_amount' => ['nullable', 'numeric', 'min:0'],
            'effective_from_year' => ['required', 'integer', 'digits:4'],
            'effective_to_year' => ['nullable', 'integer', 'digits:4', 'gte:effective_from_year'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'metadata_json' => ['nullable', 'array'],
        ];
    }
}
