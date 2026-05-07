<?php

namespace App\Http\Requests\Api\V1;

class UpdateInstitutionAnnualDeclarationRequest extends StoreInstitutionAnnualDeclarationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['supporting_document'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'];

        return $rules;
    }
}
