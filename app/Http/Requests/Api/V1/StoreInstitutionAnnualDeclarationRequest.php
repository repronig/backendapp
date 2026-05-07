<?php

namespace App\Http\Requests\Api\V1;

use App\Models\InstitutionAnnualDeclaration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInstitutionAnnualDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('institution_user') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $faculties = collect($this->input('faculties', []))
            ->filter(fn ($faculty) => is_array($faculty))
            ->map(function (array $faculty) {
                return [
                    'faculty_name' => trim((string) ($faculty['faculty_name'] ?? '')),
                    'student_count' => $faculty['student_count'] ?? null,
                ];
            })
            ->filter(fn (array $faculty) => $faculty['faculty_name'] !== '' || filled($faculty['student_count']))
            ->values()
            ->all();

        $this->merge([
            'declared_students_count' => $this->normalizeOptionalInteger('declared_students_count'),
            'declared_members_count' => $this->normalizeOptionalInteger('declared_members_count'),
            'declared_branches_count' => $this->normalizeOptionalInteger('declared_branches_count'),
            'faculties' => $faculties,
        ]);
    }

    protected function normalizeOptionalInteger(string $key): mixed
    {
        $value = $this->input($key);

        return $value === '' ? null : $value;
    }

    public function rules(): array
    {
        return [
            'licensing_year' => ['required', 'integer', 'digits:4', 'min:2000'],
            'declared_students_count' => ['nullable', 'integer', 'min:0'],
            'declared_members_count' => ['nullable', 'integer', 'min:0'],
            'declared_branches_count' => ['nullable', 'integer', 'min:0'],
            'faculties' => ['nullable', 'array'],
            'faculties.*.faculty_name' => ['required_with:faculties', 'string', 'max:255'],
            'faculties.*.student_count' => ['required_with:faculties', 'integer', 'min:0'],
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'metadata_json' => ['nullable', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $institution = $this->user()?->primaryInstitutionUser?->institution;
            $type = $institution?->institution_type;
            $academicTypes = ['university', 'polytechnic', 'college_of_education', 'research_institute'];
            $licensingYear = (int) $this->input('licensing_year');

            if (
                $institution
                && $licensingYear > 0
                && InstitutionAnnualDeclaration::query()
                    ->where('institution_id', $institution->id)
                    ->where('licensing_year', $licensingYear)
                    ->exists()
            ) {
                $validator->errors()->add('licensing_year', 'A declaration already exists for this licensing year.');
            }

            if (in_array($type, $academicTypes, true)) {
                $sum = collect($this->input('faculties', []))->sum(fn (array $faculty) => (int) ($faculty['student_count'] ?? 0));
                $declared = (int) $this->input('declared_students_count', 0);

                if ($declared <= 0) {
                    $validator->errors()->add('declared_students_count', 'Declared students count is required for universities, polytechnics, colleges of education, and research institutes.');
                }

                if (count($this->input('faculties', [])) === 0) {
                    $validator->errors()->add('faculties', 'At least one faculty is required for universities, polytechnics, colleges of education, and research institutes.');
                }

                if ($sum !== $declared) {
                    $validator->errors()->add('faculties', 'The sum of faculty student counts must equal the declared students count.');
                }
            }

            if (! in_array($type, $academicTypes, true)) {
                if (! $this->filled('declared_members_count')) {
                    $validator->errors()->add('declared_members_count', 'Declared members count is required for this institution type.');
                }

                if (! $this->filled('declared_branches_count')) {
                    $validator->errors()->add('declared_branches_count', 'Declared branches count is required for this institution type.');
                }
            }
        });
    }
}
