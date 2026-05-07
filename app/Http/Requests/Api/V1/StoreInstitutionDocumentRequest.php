<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitutionDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('institution_user') ?? false;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', 'in:cac_certificate,proof_of_address'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}