<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApplyLicenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('institution_user') ?? false;
    }

    public function rules(): array
    {
        return [
            'licence_year' => ['required', 'integer', 'digits:4'],
        ];
    }
}
