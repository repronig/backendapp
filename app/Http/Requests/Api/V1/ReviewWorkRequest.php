<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReviewWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', 'in:verified,approved,rejected,changes_requested,restricted,disputed'],
            'reason_code' => ['nullable', 'string', 'max:50'],
            'review_note' => ['nullable', 'string'],
            'evidence_requested' => ['nullable', 'boolean'],
        ];
    }
}
