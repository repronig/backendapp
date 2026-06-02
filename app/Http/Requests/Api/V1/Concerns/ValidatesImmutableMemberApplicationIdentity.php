<?php

namespace App\Http\Requests\Api\V1\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesImmutableMemberApplicationIdentity
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->exists('applicant_type')) {
                $validator->errors()->add(
                    'applicant_type',
                    'Applicant type cannot be changed after registration.'
                );
            }

            if ($this->exists('association_id')) {
                $validator->errors()->add(
                    'association_id',
                    'Association cannot be changed after registration.'
                );
            }
        });
    }
}
