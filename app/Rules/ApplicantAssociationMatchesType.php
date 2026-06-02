<?php

namespace App\Rules;

use App\Models\Association;
use App\Support\Membership\ApplicantAssociationMap;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ApplicantAssociationMatchesType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $applicantType = (string) request()->input('applicant_type', '');

        if ($applicantType === '' || ! in_array($applicantType, ApplicantAssociationMap::APPLICANT_TYPES, true)) {
            return;
        }

        $association = Association::query()->find($value);

        if ($association === null) {
            return;
        }

        if (! ApplicantAssociationMap::associationMatchesApplicantType($applicantType, $association->code)) {
            $fail('The selected association is not valid for the chosen applicant type.');
        }
    }
}
