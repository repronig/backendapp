<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', 'string', 'in:member,institution,association,work'],
            'target_id' => ['required', 'integer'],
            'category' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'visibility' => ['nullable', 'string', 'in:private,restricted,internal'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }
}
