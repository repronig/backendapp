<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProcessMemberWorkImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('member') ?? false;
    }

    public function rules(): array
    {
        return [
            'agreement_accepted' => ['required', 'accepted'],
            'date_of_consent' => ['required', 'date'],
        ];
    }
}
