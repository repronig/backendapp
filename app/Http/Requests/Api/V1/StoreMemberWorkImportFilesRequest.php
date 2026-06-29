<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberWorkImportFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('member') ?? false;
    }

    public function rules(): array
    {
        $maxKb = (int) config('member_work_imports.max_zip_size_kb', 512000);

        return [
            'file' => ['required', 'file', 'mimes:zip', 'max:'.$maxKb],
        ];
    }
}
