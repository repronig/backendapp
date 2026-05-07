<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsageDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('institution_user') ?? false;
    }

    public function rules(): array
    {
        return [
            'reporting_year' => ['required', 'integer', 'digits:4'],
            'declared_student_population' => ['nullable', 'integer', 'min:0'],
            'declared_academic_staff_count' => ['nullable', 'integer', 'min:0'],
            'declared_administrative_staff_count' => ['nullable', 'integer', 'min:0'],
            'declared_campuses_count' => ['nullable', 'integer', 'min:0'],
            'declared_library_capacity' => ['nullable', 'integer', 'min:0'],
            'declaration_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}