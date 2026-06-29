<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberWorkImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('member') ?? false;
    }

    public function rules(): array
    {
        $maxKb = (int) config('member_work_imports.max_csv_size_kb', 10240);

        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:'.$maxKb],
        ];
    }
}
