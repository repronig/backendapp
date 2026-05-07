<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'import_type' => ['required', 'string', 'in:members,works,institutions'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ];
    }
}
