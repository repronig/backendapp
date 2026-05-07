<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('institution_user') ?? false;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'visibility' => ['nullable', 'string', 'in:private,restricted,internal'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ];
    }
}
