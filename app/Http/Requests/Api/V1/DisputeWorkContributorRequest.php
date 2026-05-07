<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class DisputeWorkContributorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['nullable', 'string', 'max:50'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
