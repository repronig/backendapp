<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AcceptInstitutionLicensingTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('institution_user') ?? false;
    }

    public function rules(): array
    {
        return [
            'terms_version' => ['required', 'string', 'max:64'],
            'acknowledged_on' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
            'confirm_accepted' => ['required', 'accepted'],
        ];
    }
}
